<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Call;
use Murtukov\PHPCodeGenerator\Config;
use Murtukov\PHPCodeGenerator\ConverterInterface;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\Functions\Closure;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Murtukov\PHPCodeGenerator\Utils;
use Overblog\GraphQLBundle\Error\ResolveErrors;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLGenerator\Exception\GeneratorException;
use RuntimeException;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use function array_filter;
use function array_intersect;
use function array_map;
use function array_replace_recursive;
use function count;
use function explode;
use function extract;
use function ltrim;
use function rtrim;
use function str_split;
use function strpos;
use function strrchr;
use function substr;
use function trim;

abstract class BaseBuilder implements TypeBuilderInterface
{
    protected const DOCBLOCK_TEXT = "This file was generated and should not be edited manually.";
    protected const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];
    protected const CONSTRAINTS_NAMESPACE = "Symfony\Component\Validator\Constraints";

    protected ExpressionConverter $expressionConverter;
    protected PhpFile $file;
    protected string $namespace;
    protected array $config;

    public function __construct(ExpressionConverter $expressionConverter, string $namespace)
    {
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional converter in the php code generator
        Config::registerConverter($expressionConverter, ConverterInterface::TYPE_STRING);
    }

    /**
     * @param $typeDefinition
     * @return GeneratorInterface|string
     * @throws RuntimeException
     */
    protected static function buildType($typeDefinition)
    {
        $typeNode = Parser::parseType($typeDefinition);
        return self::wrapTypeRecursive($typeNode);
    }

    /**
     * @param $typeNode
     * @return DependencyAwareGenerator|string
     * @throws RuntimeException
     */
    private static function wrapTypeRecursive($typeNode)
    {
        $call = new Call();

        switch ($typeNode->kind) {
            case NodeKind::NON_NULL_TYPE:
                $innerType = self::wrapTypeRecursive($typeNode->type);
                $type = $call(Type::class)::nonNull($innerType);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = self::wrapTypeRecursive($typeNode->type);
                $type = $call(Type::class)::listOf($innerType);
                break;
            case NodeKind::NAMED_TYPE:
                if (in_array($typeNode->name->value, self::BUILT_IN_TYPES)) {
                    $name = strtolower($typeNode->name->value);
                    $type = $call(Type::class)::$name();
                } else {
                    $name = $typeNode->name->value;
                    $type = "\$globalVariables->get('typeResolver')->resolve('$name')";
                }
                break;
            default: throw new RuntimeException('Unrecognized node kind.');
        }

        return $type;
    }

    public function buildConfigLoader(array $config)
    {
        /**
         * @var array       $fields
         * @var string|null $description
         * @var array|null  $interfaces
         * @var string|null $resolveType
         * @var string|null $validation  - only by InputType
         * @var array|null  $types       - only by UnionType
         * @var array|null  $values      - only by EnumType
         */
        extract($config);

        $configLoader = AssocArray::multiline();
        $configLoader->addItem('name', new Literal('self::NAME'));

        if (isset($description)) {
            $configLoader->addItem('description', $description);
        }

        // only by InputType (class level validation)
        if (isset($validation)) {
            $configLoader->addItem('validation', $this->buildValidationRules($validation));
        }

        if (!empty($fields)) {
            $configLoader->addItem('fields', ArrowFunction::new(
                AssocArray::map($fields, [$this, 'buildField'])
            ));
        }

        if (!empty($interfaces)) {
            $items = array_map(fn($type) => "\$globalVariables->get('typeResolver')->resolve('$type')", $interfaces);
            $configLoader->addItem('interfaces', ArrowFunction::new(NumericArray::multiline($items)));
        }

        if (isset($types)) {
            $items = array_map(fn($type) => "\$globalVariables->get('typeResolver')->resolve('$type')", $types);
            $configLoader->addItem('types', ArrowFunction::new(NumericArray::multiline($items)));
        }

        if (isset($resolveType)) {
            $configLoader->addItem('resolveType', $this->buildResolveType($resolveType));
        }

        if (isset($resolveField)) {
            $configLoader->addItem('resolveField', $this->buildResolve($resolveField));
        }

        if (isset($values)) {
            $configLoader->addItem('values', AssocArray::multiline($values));
        }

        if ($this instanceof CustomScalarTypeBuilder) {
            $configLoader->addItem('scalarType', null);
            foreach (['serialize', 'parseValue', 'parseLiteral'] as $value) {
                $closure = new ArrowFunction();

                if (is_array($config[$value])) {
                    $closure->setExpression("{$config[$value][0]}::{$config[$value][1]}(...\\func_get_args())");
                } else {
                    $closure->setExpression($config[$value] . '(...\\func_get_args())');
                }

                $configLoader->addItem($value, $closure);
            }
        }

        return new ArrowFunction($configLoader);
    }

    /**
     * @param mixed      $resolve
     * @param array|null $validationConfig
     * @return Closure
     */
    public function buildResolve($resolve, ?array $validationConfig = null)
    {
        $closure = Closure::new()
            ->addArgument('value')
            ->addArgument('args')
            ->addArgument('context')
            ->addArgument('info', ResolveInfo::class)
            ->bindVar('globalVariables');

        // TODO (murtukov): replace usage of converter with ExpressionLanguage static method
        if ($this->expressionConverter->check($resolve)) {
            $injectErrors = ExpressionLanguage::expressionContainsVar('errors', $resolve);

            if ($injectErrors) {
                $closure->append('$errors = ', Instance::new(ResolveErrors::class));
            }

            $injectValidator = ExpressionLanguage::expressionContainsVar('validator', $resolve);

            if (null !== $validationConfig) {
                $this->buildValidator($closure, $validationConfig, $injectValidator, $injectErrors);
            } elseif (true === $injectValidator) {
                throw new GeneratorException(
                    'Unable to inject an instance of the InputValidator. No validation constraints provided. '.
                    'Please remove the "validator" argument from the list of dependencies of your resolver '.
                    'or provide validation configs.'
                );
            }

            $closure->append('return ', $this->expressionConverter->convert($resolve));
            return $closure;
        }

        $closure->append('return ', Utils::stringify($resolve));

        return $closure;
    }

    public function buildValidator(Closure $closure, array $mapping, bool $injectValidator, bool $injectErrors)
    {
        $validator = Instance::new(InputValidator::class)
            ->setMultiline()
            ->addArgument(new Literal("\\func_get_args()"))
            ->addArgument("\$globalVariables->get('container')->get('validator')")
            ->addArgument("\$globalVariables->get('validatorFactory')");

        if (!empty($mapping['properties'])) {
            $validator->addArgument($this->buildProperties($mapping['properties']));
        } else {
            $validator->addArgument([]);
        }

        if (!empty($mapping['class'])){
            $validator->addArgument($this->buildValidationRules($mapping['class']));
        }

        $closure->append('$validator = ', $validator);

        // If auto-validation on or errors are injected
        if (!$injectValidator || $injectErrors) {
            if (!empty($mapping['validationGroups'])) {
                $validationGroups = NumericArray::new($mapping['validationGroups']);
            } else {
                $validationGroups = 'null';
            }

            $closure->emptyLine();

            if ($injectErrors) {
                $closure->append('$errors->setValidationErrors($validator->validate(', $validationGroups, ', false))');
            } else {
                $closure->append('$validator->validate(', $validationGroups, ')');
            }

            $closure->emptyLine();
        }
    }

    public function buildValidationRules($mapping)
    {
        /**
         * @var array  $constraints
         * @var string $link
         * @var array  $cascade
         */
        extract($mapping);

        $array = AssocArray::multiline();

        if (!empty($link)) {
            if (strpos($link, '::') === false) {
                // e.g.: App\Entity\Droid
                $array->addItem('link', $link);
            } else {
                // e.g. App\Entity\Droid::$id
                $array->addItem('link', NumericArray::new($this->normalizeLink($link)));
            }
        }

        if (!empty($cascade)) {
            $array->addItem('cascade', $this->buildCascade($cascade));
        }

        if (!empty($constraints)) {
            // If there are only constarainst, dont use additional nesting
            if ($array->count() === 0) {
                return $this->buildConstraints($constraints);
            }
            $array->addItem('constraints', $this->buildConstraints($constraints));
        }

        return $array;
    }

    /**
     * <code>
     * [
     *     new NotNull(),
     *     new Length(['min' => 5, 'max' => 10]),
     *     ...
     * ]
     * </code>
     */
    public function buildConstraints(array $constraints = [])
    {
        $result = NumericArray::multiline();

        foreach ($constraints as $wrapper) {
            $name = key($wrapper);
            $args = reset($wrapper);

            if (false !== strpos($name, '\\')) {
                // Custom constraint
                $fqcn = ltrim($name, '\\');
                $name = ltrim(strrchr($name, '\\'));
                $this->file->addUse($fqcn);
            } else {
                // Symfony constraint
                $this->file->addUseGroup(self::CONSTRAINTS_NAMESPACE, $name);
                $fqcn = self::CONSTRAINTS_NAMESPACE . "\\$name";
            }

            if (!\class_exists($fqcn)) {
                throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
            }

            $instance = Instance::new($name);

            if (is_array($args)) {
                if (isset($args[0]) && is_array($args[0])) {
                    $instance->addArgument($this->buildConstraints($args));
                } else {
                    // Numeric or Assoc array?
                    $instance->addArgument(isset($args[0]) ? $args : AssocArray::new($args));
                }
            } elseif(null !== $args) {
                $instance->addArgument($args);
            }

            $result->push($instance);
        }

//        if ($result->count() === 1) {
//            return $result->getFirstItem();
//        }

        return $result;
    }

    // TODO: throw on scalar types
    public function buildCascade(array $cascade)
    {
        if (empty($cascade)) {
            return null;
        }

        /**
         * @var string $referenceType
         * @var array  $groups
         * @var bool   $isCollection
         */
        extract($cascade);

        $result = AssocArray::multiline()
            ->addIfNotEmpty('groups', $groups);

        if (isset($isCollection)) {
            $result->addItem('isCollection', $isCollection);
        }

        if (isset($referenceType)) {
            $result->addIfNotNull('referenceType', "\$globalVariables->get('typeResolver')->resolve('$referenceType')");
        }

        return $result;
    }

    public function buildProperties(?array $properties)
    {
        $array = AssocArray::multiline();

        foreach ($properties as $name => $props) {
            $array->addItem($name, $this->buildValidationRules($props));
        }

        return $array;
    }

    /**
     * @param array $fieldConfig
     * @return GeneratorInterface|AssocArray|string
     */
    public function buildField(array $fieldConfig /*, $fieldname */)
    {
        /**
         * @var string      $type
         * @var string|null $resolve
         * @var string|null $description
         * @var array|null  $args
         * @var string|null $complexity
         * @var string|null $deprecationReason
         */
        extract($fieldConfig);

        // If there is only 'type', use shorthand
        if (count($fieldConfig) === 1 && isset($type)) {
            return self::buildType($type);
        }

        $field = AssocArray::multiline()
            ->addItem('type', self::buildType($type));

        // only for object types
        if (isset($resolve)) {
            $validationConfig = $this->restructureObjectValidationConfig($fieldConfig, $field);
            $field->addItem('resolve', $this->buildResolve($resolve, $validationConfig));
        }

        if (isset($deprecationReason)) {
            $field->addItem('deprecationReason', $deprecationReason);
        }

        if (isset($description)) {
            $field->addItem('description', $description);
        }

        if (!empty($args)) {
            $field->addItem('args', NumericArray::map($args, [$this, 'buildArg']));
        }

        if (isset($complexity)) {
            $field->addItem('complexity', $this->buildComplexity($complexity));
        }

        if (isset($public)) {
            $field->addItem('public', $this->buildPublic($public));
        }

        if (isset($access)) {
            $field->addItem('access', $this->buildAccess($access));
        }

        if ($this instanceof InputTypeBuilder && isset($validation)) {
            $this->restructureInputValidationConfig($fieldConfig);
            $field->addItem('validation', $this->buildValidationRules($fieldConfig['validation']));
        }

        // TODO: ind out where this is used. Maybe in onjunction with resolveField?
        $field->addItem('useStrictAccess', true);

        return $field;
    }

    public function buildArg($argConfig, $argName)
    {
        /**
         * @var string      $type
         * @var string|null $description
         * @var string|null $defaultValue
         */
        extract($argConfig);

        $arg = AssocArray::multiline()
            ->addItem('name', $argName)
            ->addItem('type', self::buildType($type));

        if (isset($description)) {
            $arg->addIfNotEmpty('description', $description);
        }

        if (isset($defaultValue)) {
            $arg->addIfNotEmpty('defaultValue', $defaultValue);
        }

        return $arg;
    }

    /**
     * @param string|int $complexity
     * @return Closure|mixed
     */
    public function buildComplexity($complexity)
    {
        if ($this->expressionConverter->check($complexity)) {
            $expression = $this->expressionConverter->convert($complexity);

            if (ExpressionLanguage::expressionContainsVar('args', $complexity)) {
                return Closure::new()
                    ->addArgument('childrenComplexity')
                    ->addArgument('arguments', '', [])
                    ->bindVar('globalVariables')
                    ->append('$args = $globalVariables->get(\'argumentFactory\')->create($arguments)')
                    ->append('return ', $expression)
                ;
            }

            return ArrowFunction::new($expression)->addArgument('childrenComplexity');
        }

        return $complexity;
    }

    public function buildPublic($public)
    {
        if ($this->expressionConverter->check($public)) {
            $expression = $this->expressionConverter->convert($public);

            return ArrowFunction::new($expression)
                ->addArgument('fieldName')
                ->addArgument('typeName', '', new Literal('self::NAME'))
            ;
        }

        return $public;
    }

    public function buildAccess($access)
    {
        if ($this->expressionConverter->check($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::new()
                ->addArgument('value')
                ->addArgument('args')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->addArgument('object')
                ->setExpression($expression);
        }

        return $access;
    }

    public function buildResolveType($resolveType)
    {
        if ($this->expressionConverter->check($resolveType)) {
            $expression = $this->expressionConverter->convert($resolveType);

            return ArrowFunction::new()
                ->addArgument('value')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->setExpression($expression);
        }

        return $resolveType;
    }

    // TODO (murtukov): rework this method to use builders
    private function restructureInputValidationConfig(array &$fieldConfig)
    {
        if (empty($fieldConfig['validation']['cascade'])) {
            return;
        }

        $fieldConfig['validation']['cascade']['isCollection'] = $this->isCollectionType($fieldConfig['type']);
        $fieldConfig['validation']['cascade']['referenceType'] = trim($fieldConfig['type'], '[]!');
    }

    // TODO (murtukov): rework this method to use builders
    protected function restructureObjectValidationConfig(array $fieldConfig, AssocArray $field): ?array
    {
        $properties = [];

        foreach ($fieldConfig['args'] ?? [] as $name => $arg) {
            if (empty($arg['validation'])) {
                continue;
            }

            $properties[$name] = $arg['validation'];


            if (empty($arg['validation']['cascade'])) {
                continue;
            }

            $properties[$name]['cascade']['isCollection'] = $this->isCollectionType($arg['type']);
            $properties[$name]['cascade']['referenceType'] = trim($arg['type'], '[]!');
        }

        // Merge class and field constraints
        $classValidation = $this->config['validation'] ?? [];

        if (!empty($fieldConfig['validation'])) {
            $classValidation = array_replace_recursive($classValidation, $fieldConfig['validation']);
        }

        $mapping = [];

        if (!empty($properties)) {
            $mapping['properties'] = $properties;
        }

        // class
        if (!empty($classValidation)) {
            $mapping['class'] = $classValidation;
        }

        // validationGroups
        if (!empty($fieldConfig['validationGroups'])) {
            $mapping['validationGroups'] = $fieldConfig['validationGroups'];
        }

        if (empty($classValidation) && !array_filter($properties)) {
            return null;
        } else {
            return $mapping;
        }
    }

    protected function isCollectionType(string $type): bool
    {
        return 2 === count(array_intersect(['[', ']'], str_split($type)));
    }

    /**
     * Creates and array from a formatted string, e.g.:
     *
     * <code>
     *    "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *    "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *    "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     * </code>
     *
     * @param string $link
     *
     * @return array
     */
    protected function normalizeLink(string $link): array
    {
        [$fqcn, $classMember] = explode('::', $link);

        if ('$' === $classMember[0]) {
            return [$fqcn, ltrim($classMember, '$'), 'property'];
        } elseif (')' === substr($classMember, -1)) {
            return [$fqcn, rtrim($classMember, '()'), 'getter'];
        } else {
            return [$fqcn, $classMember, 'member'];
        }
    }
}
