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
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Validator\InputValidator;
use RuntimeException;
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
use function substr;
use function trim;

abstract class BaseBuilder implements TypeBuilderInterface
{
    protected const DOCBLOCK_TEXT = "This file was generated and should not be edited manually.";
    protected const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];

    protected ExpressionConverter $expressionConverter;
    protected string $namespace;
    protected array $config;
    protected PhpFile $file;

    public function __construct(ExpressionConverter $expressionConverter, string $namespace)
    {
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional stringifier for the php code generator
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
                $type = $call(Type::class)::notNull($innerType);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = self::wrapTypeRecursive($typeNode->type);
                $type = $call(Type::class)::listOf($innerType);
                break;
            case NodeKind::NAMED_TYPE:
                if (in_array($typeNode->name->value, self::BUILT_IN_TYPES)) {
                    $name = lcfirst($typeNode->name->value);
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

    public function buildConfigLoader($config)
    {
        /**
         * @var array       $fields
         * @var string|null $description
         * @var array|null  $interfaces
         * @var string|null $resolveType
         */
        extract($config);

        $configLoader = AssocArray::multiline()
            ->addItem('name', new Literal('$this->name'))
            ->addItem('fields', ArrowFunction::new(
                AssocArray::map($fields, [$this, 'buildField'])
            ));

        if (isset($description)) {
            $configLoader->addItem('description', $description);
        }

        if (!empty($interfaces)) {
            $items = array_map(fn($type) => "\$globalVariables->get('typeResolver')->resolve('$type')", $interfaces);

            if (count($interfaces) > 1) {
                $array = NumericArray::multiline($items);
            } else {
                $array = NumericArray::new($items);
            }

            $configLoader->addItem('interfaces', ArrowFunction::new($array));
        }

        if (isset($resolveType)) {
            $configLoader->addItem('resolveType', $this->buildResolveType($resolveType));
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
        // TODO: replace usage of converter with ExpressionLanguage static method
        if ($this->expressionConverter->check($resolve)) {
            $closure = Closure::new()
                ->addArgument('value')
                ->addArgument('args')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->bindVar('globalVariables');

            if (null !== $validationConfig) {
                $this->buildValidator($validationConfig, $closure);
            }

            $closure->append('return = ', $this->expressionConverter->convert($resolve));

            return $closure;
        }

        return $resolve;
    }

    public function buildValidator(array $mapping, Closure $closure)
    {
        $validator = Instance::new(InputValidator::class)
            ->setMultiline()
            ->addArgument(new Literal("func_get_args()"))
            ->addArgument("\$globalVariables->get('container')->get('validator')")
            ->addArgument("\$globalVariables->get('validatorFactory')")
            ->addArgument($this->buildValidationMapping($mapping))
        ;
        $closure->append('$validator = ', $validator);
    }

    public function buildValidationMapping(array $mapping)
    {
        $mappingArray = AssocArray::multiline();

        // Class validation rules
        if (!empty($mapping['class'])) {
            $mappingArray->addItem('class', $this->buildValidationRules($mapping['class']));
        }

        // Properties validation rules
        $mappingArray->addItem('properties', $this->buildProperties($mapping['properties']));

        return $mappingArray;
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

        if (!empty($constraints)) {
            $array->addItem('constraints', $this->buildConstraints($constraints));
        }

        if (!empty($link)) {
            $array->addItem('link', NumericArray::new($this->normalizeLink($link)));
        }

        if (!empty($cascade)) {
            $array->addItem('cascade', $this->buildCascade($cascade));
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

            $instance = Instance::new($name);

            if (is_array($args)) {
                // Numeric or Assoc array?
                $instance->addArgument(isset($args[0]) ? $args : AssocArray::new($args));
            }

            $result->push($instance);
            $this->file->addUseGroup('Symfony\Component\Validator\Constraints', $name);
        }

        return $result;
    }

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

        if (count($fieldConfig) === 1 && isset($type)) {
            return self::buildType($type);
        }

        $field = AssocArray::multiline()
            ->addItem('type', self::buildType($type));

        if (isset($resolve)) {
            $validationConfig = $this->processValidationConfig($fieldConfig);
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

        if (isset($validation)) {
            $field->addItem('validation', $this->buildValidationRules($validation));
        }

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
                ->addArgument('fieldName', '', new Literal('self::NAME'))
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

    // Optimize methods below

    protected function processValidationConfig(array $fieldConfig): ?array
    {
        $properties = [];

        foreach ($fieldConfig['args'] ?? [] as $name => $arg) {
            if (empty($arg['validation'])) {
                $properties[$name] = null;
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

        if (isset($fieldConfig['validation'])) {
            $classValidation = array_replace_recursive($classValidation, $fieldConfig['validation']);
        }

        $mapping = [
            'class' => empty($classValidation) ? null : $classValidation,
            'properties' => $properties,
        ];

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
     * ```
     *  "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *  "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *  "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     * ```.
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
