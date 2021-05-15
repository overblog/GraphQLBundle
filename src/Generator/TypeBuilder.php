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
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Murtukov\PHPCodeGenerator\Utils;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use Overblog\GraphQLBundle\Error\ResolveErrors;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage as EL;
use Overblog\GraphQLBundle\Generator\Config\Arg;
use Overblog\GraphQLBundle\Generator\Config\Callback;
use Overblog\GraphQLBundle\Generator\Config\Field;
use Overblog\GraphQLBundle\Generator\Config\Validation;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Validator\InputValidator;
use function array_map;
use function class_exists;
use function explode;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;
use function key;
use function ltrim;
use function reset;
use function rtrim;
use function strtolower;
use function substr;

/**
 * Service that exposes a single method `build` called for each GraphQL
 * type config to build a PhpFile object.
 *
 * {@link https://github.com/murtukov/php-code-generator}
 *
 * It's responsible for building all GraphQL types (object, input-object,
 * interface, union, enum and custom-scalar).
 *
 * Every method with prefix 'build' has a render example in it's PHPDoc.
 */
final class TypeBuilder
{
    private const CONSTRAINTS_NAMESPACE = 'Symfony\Component\Validator\Constraints';
    private const DOCBLOCK_TEXT = 'THIS FILE WAS GENERATED AND SHOULD NOT BE EDITED MANUALLY.';
    private const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];

    private const EXTENDS = [
        'object' => ObjectType::class,
        'input-object' => InputObjectType::class,
        'interface' => InterfaceType::class,
        'union' => UnionType::class,
        'enum' => EnumType::class,
        'custom-scalar' => CustomScalarType::class,
    ];

    private ExpressionConverter $expressionConverter;
    private PhpFile $file;
    private string $namespace;
    private Config\Config $config;
    private string $type;
    private string $currentField;
    private string $gqlServices = '$'.TypeGenerator::GRAPHQL_SERVICES;

    public function __construct(ExpressionConverter $expressionConverter, string $namespace)
    {
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional converter in the php code generator
        Config::registerConverter($expressionConverter, ConverterInterface::TYPE_STRING);
    }

    /**
     * @param array{
     *     name:          string,
     *     class_name:    string,
     *     fields:        array,
     *     description?:  string,
     *     interfaces?:   array,
     *     resolveType?:  string,
     *     validation?:   array,
     *     types?:        array,
     *     values?:       array,
     *     serialize?:    callable,
     *     parseValue?:   callable,
     *     parseLiteral?: callable,
     * } $config
     *
     * @throws GeneratorException
     */
    public function build(array $config, string $type): PhpFile
    {
        // This values should be accessible from every method
        $this->config = new Config\Config($config);
        $this->type = $type;

        $this->file = PhpFile::new()->setNamespace($this->namespace);

        $class = $this->file->createClass($this->config->className)
            ->setFinal()
            ->setExtends(static::EXTENDS[$type])
            ->addImplements(GeneratedTypeInterface::class, AliasedInterface::class)
            ->addConst('NAME', $config['name'])
            ->setDocBlock(static::DOCBLOCK_TEXT);

        $class->emptyLine();

        $class->createConstructor()
            ->addArgument('configProcessor', ConfigProcessor::class)
            ->addArgument(TypeGenerator::GRAPHQL_SERVICES, GraphQLServices::class)
            ->append('$config = ', $this->buildConfig())
            ->emptyLine()
            ->append('parent::__construct($configProcessor->process($config))');

        $class->createMethod('getAliases', 'public')
            ->setStatic()
            ->setReturnType('array')
            ->setDocBlock('{@inheritdoc}')
            ->append('return [self::NAME]');

        return $this->file;
    }

    /**
     * Converts a native GraphQL type string into the `webonyx/graphql-php`
     * type literal. References to user-defined types are converted into
     * TypeResovler method call and wrapped into a closure.
     *
     * Render examples:
     *
     *  -   "String"   -> Type::string()
     *  -   "String!"  -> Type::nonNull(Type::string())
     *  -   "[String!] -> Type::listOf(Type::nonNull(Type::string()))
     *  -   "[Post]"   -> Type::listOf($services->getType('Post'))
     *
     * @return GeneratorInterface|string
     */
    private function buildType(string $typeDefinition)
    {
        $typeNode = Parser::parseType($typeDefinition);

        $isReference = false;
        $type = $this->wrapTypeRecursive($typeNode, $isReference);

        if ($isReference) {
            // References to other types should be wrapped in a closure
            // for performance reasons
            return ArrowFunction::new($type);
        }

        return $type;
    }

    /**
     * Used by {@see buildType}.
     *
     * @param mixed $typeNode
     *
     * @return Literal|string
     */
    private function wrapTypeRecursive($typeNode, bool &$isReference)
    {
        switch ($typeNode->kind) {
            case NodeKind::NON_NULL_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type, $isReference);
                $type = Literal::new("Type::nonNull($innerType)");
                $this->file->addUse(Type::class);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type, $isReference);
                $type = Literal::new("Type::listOf($innerType)");
                $this->file->addUse(Type::class);
                break;
            default: // NodeKind::NAMED_TYPE
                if (in_array($typeNode->name->value, static::BUILT_IN_TYPES)) {
                    $name = strtolower($typeNode->name->value);
                    $type = Literal::new("Type::$name()");
                    $this->file->addUse(Type::class);
                } else {
                    $name = $typeNode->name->value;
                    $type = "$this->gqlServices->getType('$name')";
                    $isReference = true;
                }
                break;
        }

        return $type;
    }

    /**
     * Builds a config array compatible with webonyx/graphql-php type system. The content
     * of the array depends on the GraphQL type that is currently being generated.
     *
     * Render example (object):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Root query type',
     *          'fields' => fn() => [
     *              'posts' => {@see buildField},
     *              'users' => {@see buildField},
     *               ...
     *           ],
     *           'interfaces' => fn() => [
     *               $services->getType('PostInterface'),
     *               ...
     *           ],
     *           'resolveField' => {@see buildResolver},
     *      ]
     *
     * Render example (input-object):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'validation' => {@see buildValidationRules}
     *          'fields' => fn() => [
     *              {@see buildField},
     *               ...
     *           ],
     *      ]
     *
     * Render example (interface)
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'fields' => fn() => [
     *              {@see buildField},
     *               ...
     *           ],
     *          'resolveType' => {@see buildTypeResolver},
     *      ]
     *
     * Render example (union):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description.',
     *          'types' => fn() => [
     *              $services->getType('Photo'),
     *              ...
     *          ],
     *          'resolveType' => {@see buildTypeResolver},
     *      ]
     *
     * Render example (custom-scalar):
     *
     *      [
     *          'name' => self::NAME,
     *          'description' => 'Some description'
     *          'serialize' => {@see buildScalarCallback},
     *          'parseValue' => {@see buildScalarCallback},
     *          'parseLiteral' => {@see buildScalarCallback},
     *      ]
     *
     * Render example (enum):
     *
     *      [
     *          'name' => self::NAME,
     *          'values' => [
     *              'PUBLISHED' => ['value' => 1],
     *              'DRAFT' => ['value' => 2],
     *              'STANDBY' => [
     *                  'value' => 3,
     *                  'description' => 'Waiting for validation',
     *              ],
     *              ...
     *          ],
     *      ]
     *
     * @throws GeneratorException
     */
    private function buildConfig(): Collection
    {
        $configLoader = Collection::assoc();
        $configLoader->addItem('name', new Literal('self::NAME'));

        if ($this->config->description) {
            $configLoader->addItem('description', $this->config->description);
        }

        // only by input-object types (for class level validation)
        if (null !== $this->config->validation) {
            $configLoader->addItem('validation', $this->buildValidationRules($this->config->validation));
        }

        // only by object, input-object and interface types
        if (!empty($this->config->fields)) {
            $configLoader->addItem('fields', ArrowFunction::new(
                Collection::map($this->config->fields, [$this, 'buildField'])
            ));
        }

        if (!empty($this->config->interfaces)) {
            $items = array_map(fn ($type) => "$this->gqlServices->getType('$type')", $this->config->interfaces);
            $configLoader->addItem('interfaces', ArrowFunction::new(Collection::numeric($items, true)));
        }

        if (!empty($this->config->types)) {
            $items = array_map(fn ($type) => "$this->gqlServices->getType('$type')", $this->config->types);
            $configLoader->addItem('types', ArrowFunction::new(Collection::numeric($items, true)));
        }

        if (null !== $this->config->typeResolver) {
            $configLoader->addItem('resolveType', $this->buildTypeResolver($this->config->typeResolver));
        }

        if (null !== $this->config->fieldResolver) {
            $configLoader->addItem('resolveField', $this->buildResolver($this->config->fieldResolver));
        }

        // only by enum types
        if (null !== $this->config->values) {
            $configLoader->addItem('values', Collection::assoc($this->config->values));
        }

        // only by custom-scalar types
        if ('custom-scalar' === $this->type) {
            if ($this->config->scalarType) {
                $configLoader->addItem('scalarType', $this->config->scalarType);
            }

            if (null !== $this->config->serialize) {
                $configLoader->addItem('serialize', $this->buildScalarCallback($this->config->serialize, 'serialize'));
            }

            if (null !== $this->config->parseValue) {
                $configLoader->addItem('parseValue', $this->buildScalarCallback($this->config->parseValue, 'parseValue'));
            }

            if (null !== $this->config->parseLiteral) {
                $configLoader->addItem('parseLiteral', $this->buildScalarCallback($this->config->parseLiteral, 'parseLiteral'));
            }
        }

        return $configLoader;
    }

    /**
     * Builds an arrow function that calls a static method.
     *
     * Render example:
     *
     *      fn() => MyClassName::myMethodName(...\func_get_args())
     *
     * @param callable $callback - a callable string or a callable array
     *
     * @throws GeneratorException
     *
     * @return ArrowFunction
     */
    private function buildScalarCallback($callback, string $fieldName)
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

        if ($className === $this->config->className) {
            // Create an alias if name of serializer is same as type name
            $className = 'Base'.$className;
            $this->file->addUse($class, $className);
        } else {
            $this->file->addUse($class);
        }

        $closure->setExpression(Literal::new("$className::$method(...\\func_get_args())"));

        return $closure;
    }

    /**
     * Builds a resolver closure that contains the compiled result of user-defined
     * expression and optionally the validation logic.
     *
     * Render example (no expression language):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          return "Hello, World!";
     *      }
     *
     * Render example (with expression language):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          return $services->mutation("my_resolver", $args);
     *      }
     *
     * Render example (with validation):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          $validator = $services->createInputValidator(...func_get_args());
     *          return $services->mutation("create_post", $validator]);
     *      }
     *
     * Render example (with validation, but errors are injected into the user-defined resolver):
     * {@link https://github.com/overblog/GraphQLBundle/blob/master/docs/validation/index.md#injecting-errors}
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          $errors = new ResolveErrors();
     *          $validator = $services->createInputValidator(...func_get_args());
     *
     *          $errors->setValidationErrors($validator->validate(null, false))
     *
     *          return $services->mutation("create_post", $errors);
     *      }
     *
     * @throws GeneratorException
     */
    private function buildResolver(Callback $resolver, ?array $groups = null): ?GeneratorInterface
    {
        // TODO: before creating an input validator, check if any validation rules are defined
        return $this->buildCallback(
            $resolver,
            ['value', 'args', 'context', 'info'],
            function (string $expression) use ($groups) {
                $closure = Closure::new()
                    ->addArguments('value', 'args', 'context', 'info')
                    ->bindVar(TypeGenerator::GRAPHQL_SERVICES);

                $injectValidator = EL::expressionContainsVar('validator', $expression);

                if ($this->configContainsValidation()) {
                    $injectErrors = EL::expressionContainsVar('errors', $expression);

                    if ($injectErrors) {
                        $closure->append('$errors = ', Instance::new(ResolveErrors::class));
                    }

                    $closure->append('$validator = ', "$this->gqlServices->createInputValidator(...func_get_args())");

                    // If auto-validation on or errors are injected
                    if (!$injectValidator || $injectErrors) {
                        if (!empty($groups)) {
                            $validationGroups = Collection::numeric($groups);
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
                } elseif ($injectValidator) {
                    throw new GeneratorException('Unable to inject an instance of the InputValidator. No validation constraints provided. Please remove the "validator" argument from the list of dependencies of your resolver or provide validation configs.');
                }

                $closure->append('return ', $this->expressionConverter->convert($expression));

                return $closure;
            }
        );
    }

    /**
     * Checks if given config contains any validation rules.
     */
    private function configContainsValidation(): bool
    {
        $fieldConfig = $this->config->fields[$this->currentField];

        if (!empty($fieldConfig->validation)) {
            return true;
        }

        foreach ($fieldConfig->args ?? [] as $argConfig) {
            if (!empty($argConfig->validation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render example:
     *
     *      [
     *          'link' => {@see normalizeLink}
     *          'cascade' => [
     *              'groups' => ['my_group'],
     *          ],
     *          'constraints' => {@see buildConstraints}
     *      ]
     *
     * If only constraints provided, uses {@see buildConstraints} directly.
     *
     * @throws GeneratorException
     */
    private function buildValidationRules(Validation $validationConfig): GeneratorInterface
    {
        $array = Collection::assoc();

        if (null !== $validationConfig->link) {
            if (str_contains($validationConfig->link, '::')) {
                // e.g. App\Entity\Droid
                $array->addItem('link', $validationConfig->link);
            } else {
                // e.g. App\Entity\Droid::$id
                $array->addItem('link', Collection::numeric($this->normalizeLink($validationConfig->link)));
            }
        }

        if (null !== $validationConfig->cascade) {
            // If there are only constraints, use short syntax
            if (empty($validationConfig->cascade['groups'])) {
                $this->file->addUse(InputValidator::class);

                return Literal::new('InputValidator::CASCADE');
            }
            $array->addItem('cascade', $validationConfig->cascade['groups']);
        }

        if (!empty($validationConfig->constraints)) {
            // If there are only constraints, use short syntax
            if (0 === $array->count()) {
                return $this->buildConstraints($validationConfig->constraints);
            }
            $array->addItem('constraints', $this->buildConstraints($validationConfig->constraints));
        }

        return $array;
    }

    /**
     * Builds a closure or a numeric multiline array with Symfony Constraint
     * instances. The array is used by {@see InputValidator} during requests.
     *
     * Render example (array):
     *
     *      [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * Render example (in a closure):
     *
     *      fn() => [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * @throws GeneratorException
     *
     * @return ArrowFunction|Collection
     */
    private function buildConstraints(array $constraints = [], bool $inClosure = true)
    {
        $result = Collection::numeric()->setMultiline();

        foreach ($constraints as $wrapper) {
            $name = key($wrapper);
            $args = reset($wrapper);

            if (str_contains($name, '\\')) {
                // Custom constraint
                $fqcn = ltrim($name, '\\');
                $instance = Instance::new("@\\$fqcn");
            } else {
                // Symfony constraint
                $fqcn = static::CONSTRAINTS_NAMESPACE."\\$name";
                $this->file->addUse(static::CONSTRAINTS_NAMESPACE.' as SymfonyConstraints');
                $instance = Instance::new("@SymfonyConstraints\\$name");
            }

            if (!class_exists($fqcn)) {
                throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
            }

            if (is_array($args)) {
                if (isset($args[0]) && is_array($args[0])) {
                    // Nested instance
                    $instance->addArgument($this->buildConstraints($args, false));
                } else {
                    // Numeric or Assoc array?
                    $instance->addArgument(isset($args[0]) ? $args : Collection::assoc($args));
                }
            } elseif (null !== $args) {
                $instance->addArgument($args);
            }

            $result->push($instance);
        }

        if ($inClosure) {
            return ArrowFunction::new($result);
        }

        return $result; // @phpstan-ignore-line
    }

    /**
     * Render example:
     *
     *      [
     *          'type' => {@see buildType},
     *          'description' => 'Some description.',
     *          'deprecationReason' => 'This field will be removed soon.',
     *          'args' => fn() => [
     *              {@see buildArg},
     *              {@see buildArg},
     *               ...
     *           ],
     *          'resolve' => {@see buildResolver},
     *          'complexity' => {@see buildComplexity},
     *      ]
     *
     * @return GeneratorInterface|Collection|string
     *
     * @throws GeneratorException
     *
     * @internal
     */
    public function buildField(Field $fieldConfig, string $fieldName)
    {
        $this->currentField = $fieldName;

        // If there is only 'type', use shorthand
        if ($fieldConfig->hasOnlyType) {
            return $this->buildType($fieldConfig->type);
        }

        $field = Collection::assoc()
            ->addItem('type', $this->buildType($fieldConfig->type));

        // only for object types
        if (!empty($fieldConfig->resolver)) {
            if ($fieldConfig->validation) {
                $field->addItem('validation', $this->buildValidationRules($fieldConfig->validation));
            }
            $field->addItem('resolve', $this->buildResolver($fieldConfig->resolver, $fieldConfig->validationGroups ?? null));
        }

        if (null !== $fieldConfig->deprecationReason) {
            $field->addItem('deprecationReason', $fieldConfig->deprecationReason);
        }

        if (null !== $fieldConfig->description) {
            $field->addItem('description', $fieldConfig->description);
        }

        if (!empty($fieldConfig->args)) {
            $field->addItem('args', Collection::map($fieldConfig->args, [$this, 'buildArg'], false));
        }

        if (null !== $fieldConfig->complexity) {
            $field->addItem('complexity', $this->buildComplexity($fieldConfig->complexity));
        }

        if (null !== $fieldConfig->public) {
            $field->addItem('public', $this->buildPublic($fieldConfig->public));
        }

        if (null !== $fieldConfig->access) {
            $field->addItem('access', $this->buildAccess($fieldConfig->access));
        }

        if (!empty($fieldConfig->access) && is_string($fieldConfig->access) && EL::expressionContainsVar('object', $fieldConfig->access)) {
            $field->addItem('useStrictAccess', false);
        }

        if ('input-object' === $this->type) {
            if ($fieldConfig->hasDefaultValue) {
                $field->addItem('defaultValue', $fieldConfig->defaultValue);
            }

            if (null !== $fieldConfig->validation) {
                $field->addItem('validation', $this->buildValidationRules($fieldConfig->validation));
            }
        }

        return $field;
    }

    /**
     * Render example:
     * <code>
     *  [
     *      'name' => 'username',
     *      'type' => {@see buildType},
     *      'description' => 'Some fancy description.',
     *      'defaultValue' => 'admin',
     *  ]
     * </code>
     *
     * @internal
     *
     * @throws GeneratorException
     */
    public function buildArg(Arg $argConfig, string $argName): Collection
    {
        $arg = Collection::assoc()
            ->addItem('name', $argName)
            ->addItem('type', $this->buildType($argConfig->type));

        if (null !== $argConfig->description) {
            $arg->addIfNotEmpty('description', $argConfig->description);
        }

        if ($argConfig->hasDefaultValue) {
            $arg->addItem('defaultValue', $argConfig->defaultValue);
        }

        if (!empty($argConfig->validation)) {
            if (in_array($argConfig->type, self::BUILT_IN_TYPES) && null !== $argConfig->validation->cascade) {
                throw new GeneratorException('Cascade validation cannot be applied to built-in types.');
            }

            $arg->addIfNotEmpty('validation', $this->buildValidationRules($argConfig->validation));
        }

        return $arg;
    }

    /**
     * Builds a closure or an arrow function, depending on whether the `args` param is provided.
     *
     * Render example (closure):
     *
     *      function ($value, $arguments) use ($services) {
     *          $args = $services->get('argumentFactory')->create($arguments);
     *          return ($args['age'] + 5);
     *      }
     *
     * Render example (arrow function):
     *
     *      fn($childrenComplexity) => ($childrenComplexity + 20);
     *
     * @param mixed $complexity
     *
     * @return Closure|mixed
     */
    private function buildComplexity($complexity)
    {
        if (EL::isStringWithTrigger($complexity)) {
            $expression = $this->expressionConverter->convert($complexity);

            if (EL::expressionContainsVar('args', $complexity)) {
                return Closure::new()
                    ->addArgument('childrenComplexity')
                    ->addArgument('arguments', '', [])
                    ->bindVar(TypeGenerator::GRAPHQL_SERVICES)
                    ->append('$args = ', "$this->gqlServices->get('argumentFactory')->create(\$arguments)")
                    ->append('return ', $expression)
                ;
            }

            $arrow = ArrowFunction::new(is_string($expression) ? new Literal($expression) : $expression);

            if (EL::expressionContainsVar('childrenComplexity', $complexity)) {
                $arrow->addArgument('childrenComplexity');
            }

            return $arrow;
        }

        return new ArrowFunction(0);
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example (if expression):
     *
     *      fn($fieldName, $typeName = self::NAME) => ($fieldName == "name")
     *
     * @param mixed $public
     *
     * @return ArrowFunction|mixed
     */
    private function buildPublic($public)
    {
        if (EL::isStringWithTrigger($public)) {
            $expression = $this->expressionConverter->convert($public);
            $arrow = ArrowFunction::new(Literal::new($expression));

            if (EL::expressionContainsVar('fieldName', $public)) {
                $arrow->addArgument('fieldName');
            }

            if (EL::expressionContainsVar('typeName', $public)) {
                $arrow->addArgument('fieldName');
                $arrow->addArgument('typeName', '', new Literal('self::NAME'));
            }

            return $arrow;
        }

        return $public;
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example (if expression):
     *
     *      fn($value, $args, $context, $info, $object) => $services->get('private_service')->hasAccess()
     *
     * @param mixed $access
     *
     * @return ArrowFunction|mixed
     */
    private function buildAccess($access)
    {
        if (EL::isStringWithTrigger($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::new()
                ->addArguments('value', 'args', 'context', 'info', 'object')
                ->setExpression(Literal::new($expression));
        }

        return $access;
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example:
     *
     *      fn($value, $context, $info) => $services->getType($value)
     */
    private function buildTypeResolver(Callback $typeResolver): GeneratorInterface
    {
        return $this->buildCallback($typeResolver, ['value', 'context', 'info']);
    }

    protected function buildCallback(Callback $callback, array $argNames, ?callable $expressionBuilder = null): GeneratorInterface
    {
        if (null !== $callback->expression) {
            if (null === $expressionBuilder) {
                return ArrowFunction::new()
                    ->addArguments(...$argNames)
                    ->setExpression(Literal::new($this->expressionConverter->convert($callback->expression)))
                    ;
            } else {
                return $expressionBuilder($callback->expression);
            }
        } elseif (null !== $callback->id) {
            $fn = "$this->gqlServices->get('callbacks')->get('$callback->id')";
            if ($callback->method) {
                return Collection::numeric([$fn, $callback->method]);
            } else {
                return Literal::new($fn);
            }
        } else {
            return Literal::new("'$callback->method'");
        }
    }

    /**
     * Creates and array from a formatted string.
     *
     * Examples:
     *
     *      "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *      "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *      "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     */
    private function normalizeLink(string $link): array
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
