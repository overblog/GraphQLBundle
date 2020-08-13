<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Closure;
use Murtukov\PHPCodeGenerator\Config;
use Murtukov\PHPCodeGenerator\ConverterInterface;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Exception\UnrecognizedValueTypeException;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Murtukov\PHPCodeGenerator\Utils;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use Overblog\GraphQLBundle\Error\ResolveErrors;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Validator\InputValidator;
use RuntimeException;
use function array_filter;
use function array_intersect;
use function array_map;
use function array_replace_recursive;
use function class_exists;
use function count;
use function explode;
use function extract;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function key;
use function ltrim;
use function reset;
use function rtrim;
use function str_split;
use function strpos;
use function strrchr;
use function strtolower;
use function substr;
use function trim;

/**
 * TODO (murtukov):
 *  1. Add <code> docblocks for every method
 *  2. Replace hard-coded string types with constants ('object', 'input-object' etc.).
 */
class TypeBuilder
{
    protected const CONSTRAINTS_NAMESPACE = "Symfony\Component\Validator\Constraints";
    protected const DOCBLOCK_TEXT = 'THIS FILE WAS GENERATED AND SHOULD NOT BE EDITED MANUALLY.';
    protected const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];

    protected const EXTENDS = [
        'object' => ObjectType::class,
        'input-object' => InputObjectType::class,
        'interface' => InterfaceType::class,
        'union' => UnionType::class,
        'enum' => EnumType::class,
        'custom-scalar' => CustomScalarType::class,
    ];

    protected ExpressionConverter $expressionConverter;
    protected PhpFile $file;
    protected string $namespace;
    protected array $config;
    protected string $type;
    protected string $globalVars = '$'.TypeGenerator::GLOBAL_VARS;

    public function __construct(ExpressionConverter $expressionConverter, string $namespace)
    {
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional converter in the php code generator
        Config::registerConverter($expressionConverter, ConverterInterface::TYPE_STRING);
    }

    public function build(array $config, string $type): PhpFile
    {
        $this->config = $config;
        $this->type = $type;

        $this->file = PhpFile::new()->setNamespace($this->namespace);

        $class = $this->file->createClass($config['class_name'])
            ->setFinal()
            ->setExtends(self::EXTENDS[$type])
            ->addImplements(GeneratedTypeInterface::class)
            ->addConst('NAME', $config['name'])
            ->setDocBlock(self::DOCBLOCK_TEXT);

        $class->emptyLine();

        $class->createConstructor()
            ->addArgument('configProcessor', ConfigProcessor::class)
            ->addArgument(TypeGenerator::GLOBAL_VARS, GlobalVariables::class, null)
            ->append('$configLoader = ', $this->buildConfigLoader($config))
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, '.$this->globalVars.'))->load()')
            ->append('parent::__construct($config)');

        $this->file->addUse(LazyConfig::class);

        return $this->file;
    }

    /**
     * @return GeneratorInterface|string
     *
     * @throws RuntimeException
     */
    protected function buildType(string $typeDefinition)
    {
        $typeNode = Parser::parseType($typeDefinition);

        return $this->wrapTypeRecursive($typeNode);
    }

    /**
     * @param mixed $typeNode
     *
     * @return DependencyAwareGenerator|string
     *
     * @throws RuntimeException
     */
    protected function wrapTypeRecursive($typeNode)
    {
        switch ($typeNode->kind) {
            case NodeKind::NON_NULL_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type);
                $type = Literal::new("Type::nonNull($innerType)");
                $this->file->addUse(Type::class);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type);
                $type = Literal::new("Type::listOf($innerType)");
                $this->file->addUse(Type::class);
                break;
            default:
                if (in_array($typeNode->name->value, self::BUILT_IN_TYPES)) {
                    $name = strtolower($typeNode->name->value);
                    $type = Literal::new("Type::$name()");
                    $this->file->addUse(Type::class);
                } else {
                    $name = $typeNode->name->value;
                    $type = "$this->globalVars->get('typeResolver')->resolve('$name')";
                }
                break;
        }

        return $type;
    }

    protected function buildConfigLoader(array $config): ArrowFunction
    {
        /**
         * @var array         $fields
         * @var string|null   $description
         * @var array|null    $interfaces
         * @var string|null   $resolveType
         * @var array|null    $validation   - only by InputType
         * @var array|null    $types        - only by UnionType
         * @var array|null    $values       - only by EnumType
         * @var callback|null $serialize    - only by CustomScalarType
         * @var callback|null $parseValue   - only by CustomScalarType
         * @var callback|null $parseLiteral - only by CustomScalarType
         * @phpstan-ignore-next-line
         */
        extract($config);

        $configLoader = Collection::assoc();
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
                Collection::map($fields, [$this, 'buildField'])
            ));
        }

        if (!empty($interfaces)) {
            $items = array_map(fn ($type) => "$this->globalVars->get('typeResolver')->resolve('$type')", $interfaces);
            $configLoader->addItem('interfaces', ArrowFunction::new(Collection::numeric($items, true)));
        }

        if (!empty($types)) {
            $items = array_map(fn ($type) => "$this->globalVars->get('typeResolver')->resolve('$type')", $types);
            $configLoader->addItem('types', ArrowFunction::new(Collection::numeric($items, true)));
        }

        if (isset($resolveType)) {
            $configLoader->addItem('resolveType', $this->buildResolveType($resolveType));
        }

        if (isset($resolveField)) {
            $configLoader->addItem('resolveField', $this->buildResolve($resolveField));
        }

        if (isset($values)) {
            $configLoader->addItem('values', Collection::assoc($values));
        }

        if ('custom-scalar' === $this->type) {
            if (isset($scalarType)) {
                $configLoader->addItem('scalarType', $scalarType);
            }

            if (isset($serialize)) {
                $configLoader->addItem('serialize', $this->buildScalarCallback($serialize, 'serialize'));
            }

            if (isset($parseValue)) {
                $configLoader->addItem('parseValue', $this->buildScalarCallback($parseValue, 'parseValue'));
            }

            if (isset($parseLiteral)) {
                $configLoader->addItem('parseLiteral', $this->buildScalarCallback($parseLiteral, 'parseLiteral'));
            }
        }

        return new ArrowFunction($configLoader);
    }

    /**
     * @param callable $callback
     *
     * @return ArrowFunction
     *
     * @throws GeneratorException
     */
    protected function buildScalarCallback($callback, string $fieldName)
    {
        if (!is_callable($callback)) {
            throw new GeneratorException("Value of '$fieldName' is not callable.");
        }

        $closure = new ArrowFunction();

        if (!is_string($callback)) {
            [$class, $method] = $callback;
        } else {
            [$class, $method] = explode('::', $callback);
        }

        $className = Utils::resolveQualifier($class);

        if ($className === $this->config['class_name']) {
            // Create alias if name of serializer is same as type name
            $className = 'Base'.$className;
            $this->file->addUse($class, $className);
        } else {
            $this->file->addUse($class);
        }

        $closure->setExpression(Literal::new("$className::$method(...\\func_get_args())"));

        return $closure;
    }

    /**
     * @param mixed $resolve
     *
     * @return GeneratorInterface
     *
     * @throws GeneratorException
     * @throws UnrecognizedValueTypeException
     */
    protected function buildResolve($resolve, ?array $validationConfig = null)
    {
        if (is_callable($resolve) && is_array($resolve)) {
            return Collection::numeric($resolve);
        }

        $closure = Closure::new()
            ->addArguments('value', 'args', 'context', 'info')
            ->bindVar(TypeGenerator::GLOBAL_VARS);

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

    protected function buildValidator(Closure $closure, array $mapping, bool $injectValidator, bool $injectErrors): void
    {
        $validator = Instance::new(InputValidator::class)
            ->setMultiline()
            ->addArgument(new Literal('\\func_get_args()'))
            ->addArgument("$this->globalVars->get('container')->get('validator')")
            ->addArgument("$this->globalVars->get('validatorFactory')");

        if (!empty($mapping['properties'])) {
            $validator->addArgument($this->buildProperties($mapping['properties']));
        }

        if (!empty($mapping['class'])) {
            $validator->addArgument($this->buildValidationRules($mapping['class']));
        }

        $closure->append('$validator = ', $validator);

        // If auto-validation on or errors are injected
        if (!$injectValidator || $injectErrors) {
            if (!empty($mapping['validationGroups'])) {
                $validationGroups = Collection::numeric($mapping['validationGroups']);
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

    protected function buildValidationRules(array $mapping): Collection
    {
        /**
         * @var array  $constraints
         * @var string $link
         * @var array  $cascade
         * @phpstan-ignore-next-line
         */
        extract($mapping);

        $array = Collection::assoc();

        if (!empty($link)) {
            if (false === strpos($link, '::')) {
                // e.g.: App\Entity\Droid
                $array->addItem('link', $link);
            } else {
                // e.g. App\Entity\Droid::$id
                $array->addItem('link', Collection::numeric($this->normalizeLink($link)));
            }
        }

        if (!empty($cascade)) {
            $array->addItem('cascade', $this->buildCascade($cascade));
        }

        if (!empty($constraints)) {
            // If there are only constarainst, dont use additional nesting
            if (0 === $array->count()) {
                return $this->buildConstraints($constraints);
            }
            $array->addItem('constraints', $this->buildConstraints($constraints));
        }

        return $array; // @phpstan-ignore-line
    }

    /**
     * <code>
     * [
     *     new NotNull(),
     *     new Length(['min' => 5, 'max' => 10]),
     *     ...
     * ]
     * </code>.
     *
     * @throws GeneratorException
     */
    protected function buildConstraints(array $constraints = []): Collection
    {
        $result = Collection::numeric()->setMultiline();

        foreach ($constraints as $wrapper) {
            $name = key($wrapper);
            $args = reset($wrapper);

            if (false !== strpos($name, '\\')) {
                // Custom constraint
                $fqcn = ltrim($name, '\\');
                $name = ltrim(strrchr($name, '\\'), '\\');
                $this->file->addUse($fqcn);
            } else {
                // Symfony constraint
                $this->file->addUseGroup(self::CONSTRAINTS_NAMESPACE, $name);
                $fqcn = self::CONSTRAINTS_NAMESPACE."\\$name";
            }

            if (!class_exists($fqcn)) {
                throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
            }

            $instance = Instance::new($name);

            if (is_array($args)) {
                if (isset($args[0]) && is_array($args[0])) {
                    // Another instance?
                    $instance->addArgument($this->buildConstraints($args));
                } else {
                    // Numeric or Assoc array?
                    $instance->addArgument(isset($args[0]) ? $args : Collection::assoc($args));
                }
            } elseif (null !== $args) {
                $instance->addArgument($args);
            }

            $result->push($instance);
        }

        return $result; // @phpstan-ignore-line
    }

    /**
     * @throws GeneratorException
     */
    protected function buildCascade(array $cascade): Collection
    {
        /**
         * @var string $referenceType
         * @var array  $groups
         * @var bool   $isCollection
         * @phpstan-ignore-next-line
         */
        extract($cascade);

        $result = Collection::assoc()
            ->addIfNotEmpty('groups', $groups);

        if (isset($isCollection)) {
            $result->addItem('isCollection', $isCollection);
        }

        if (isset($referenceType)) {
            $type = trim($referenceType, '[]!');

            if (in_array($type, self::BUILT_IN_TYPES)) {
                throw new GeneratorException('Cascade validation cannot be applied to built-in types.');
            }

            $result->addItem('referenceType', "$this->globalVars->get('typeResolver')->resolve('$referenceType')");
        }

        return $result; // @phpstan-ignore-line
    }

    protected function buildProperties(array $properties): Collection
    {
        $array = Collection::assoc();

        foreach ($properties as $name => $props) {
            $array->addItem($name, $this->buildValidationRules($props));
        }

        return $array; // @phpstan-ignore-line
    }

    /**
     * @return GeneratorInterface|Collection|string
     *
     * @throws GeneratorException
     * @throws UnrecognizedValueTypeException
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
         * @phpstan-ignore-next-line
         */
        extract($fieldConfig);

        // If there is only 'type', use shorthand
        if (1 === count($fieldConfig) && isset($type)) {
            return $this->buildType($type);
        }

        $field = Collection::assoc()
            ->addItem('type', $this->buildType($type));

        // only for object types
        if (isset($resolve)) {
            $validationConfig = $this->restructureObjectValidationConfig($fieldConfig);
            $field->addItem('resolve', $this->buildResolve($resolve, $validationConfig));
        }

        if (isset($deprecationReason)) {
            $field->addItem('deprecationReason', $deprecationReason);
        }

        if (isset($description)) {
            $field->addItem('description', $description);
        }

        if (!empty($args)) {
            $field->addItem('args', Collection::map($args, [$this, 'buildArg'], false));
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

        if (!empty($access) && is_string($access) && ExpressionLanguage::expressionContainsVar('object', $access)) {
            $field->addItem('useStrictAccess', false);
        }

        if ('input-object' === $this->type && isset($validation)) {
            $this->restructureInputValidationConfig($fieldConfig);
            $field->addItem('validation', $this->buildValidationRules($fieldConfig['validation']));
        }

        return $field;
    }

    public function buildArg(array $argConfig, string $argName): Collection
    {
        /**
         * @var string      $type
         * @var string|null $description
         * @var string|null $defaultValue
         * @phpstan-ignore-next-line
         */
        extract($argConfig);

        $arg = Collection::assoc()
            ->addItem('name', $argName)
            ->addItem('type', $this->buildType($type));

        if (isset($description)) {
            $arg->addIfNotEmpty('description', $description);
        }

        if (isset($defaultValue)) {
            $arg->addIfNotEmpty('defaultValue', $defaultValue);
        }

        return $arg; // @phpstan-ignore-line
    }

    /**
     * @param string $complexity
     *
     * @return Closure|mixed
     */
    protected function buildComplexity($complexity)
    {
        if ($this->expressionConverter->check($complexity)) {
            $expression = $this->expressionConverter->convert($complexity);

            if (ExpressionLanguage::expressionContainsVar('args', $complexity)) {
                return Closure::new()
                    ->addArgument('childrenComplexity')
                    ->addArgument('arguments', '', [])
                    ->bindVar(TypeGenerator::GLOBAL_VARS)
                    ->append('$args = ', "$this->globalVars->get('argumentFactory')->create(\$arguments)")
                    ->append('return ', $expression)
                ;
            }

            $arrow = ArrowFunction::new(is_string($expression) ? new Literal($expression) : $expression);

            if (ExpressionLanguage::expressionContainsVar('childrenComplexity', $complexity)) {
                $arrow->addArgument('childrenComplexity');
            }

            return $arrow;
        }

        return new ArrowFunction(0);
    }

    /**
     * @param mixed $public
     *
     * @return ArrowFunction|mixed
     */
    protected function buildPublic($public)
    {
        if ($this->expressionConverter->check($public)) {
            $expression = $this->expressionConverter->convert($public);
            $arrow = ArrowFunction::new(Literal::new($expression));

            if (ExpressionLanguage::expressionContainsVar('fieldName', $public)) {
                $arrow->addArgument('fieldName');
            }

            if (ExpressionLanguage::expressionContainsVar('typeName', $public)) {
                $arrow->addArgument('fieldName');
                $arrow->addArgument('typeName', '', new Literal('self::NAME'));
            }

            return $arrow;
        }

        return $public;
    }

    /**
     * @param mixed $access
     *
     * @return ArrowFunction|mixed
     */
    protected function buildAccess($access)
    {
        if ($this->expressionConverter->check($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::new()
                ->addArguments('value', 'args', 'context', 'info', 'object')
                ->setExpression(Literal::new($expression));
        }

        return $access;
    }

    /**
     * @param mixed $resolveType
     *
     * @return mixed|ArrowFunction
     */
    protected function buildResolveType($resolveType)
    {
        if ($this->expressionConverter->check($resolveType)) {
            $expression = $this->expressionConverter->convert($resolveType);

            return ArrowFunction::new()
                ->addArguments('value', 'context', 'info')
                ->setExpression(Literal::new($expression));
        }

        return $resolveType;
    }

    // TODO (murtukov): rework this method to use builders
    protected function restructureInputValidationConfig(array &$fieldConfig): void
    {
        if (empty($fieldConfig['validation']['cascade'])) {
            return;
        }

        $fieldConfig['validation']['cascade']['isCollection'] = $this->isCollectionType($fieldConfig['type']);
        $fieldConfig['validation']['cascade']['referenceType'] = trim($fieldConfig['type'], '[]!');
    }

    // TODO (murtukov): rework this method to use builders
    protected function restructureObjectValidationConfig(array $fieldConfig): ?array
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
     * Creates and array from a formatted string, e.g.:.
     *
     * ```
     *    "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *    "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *    "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     * ```.
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
