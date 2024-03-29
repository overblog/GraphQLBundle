<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser;

use Doctrine\Common\Annotations\AnnotationException;
use Overblog\GraphQLBundle\Annotation\Annotation as Meta;
use Overblog\GraphQLBundle\Annotation as Metadata;
use Overblog\GraphQLBundle\Annotation\InputField;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\DocBlockTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\DoctrineTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\TypeGuessingException;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\TypeHintTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\PreParserInterface;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use function array_filter;
use function array_keys;
use function array_map;
use function array_unshift;
use function current;
use function file_get_contents;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;
use function trim;

use const PHP_VERSION_ID;

abstract class MetadataParser implements PreParserInterface
{
    public const ANNOTATION_NAMESPACE = 'Overblog\GraphQLBundle\Annotation\\';
    public const METADATA_FORMAT = '%s';

    private static ClassesTypesMap $map;
    private static array $typeGuessers = [];
    private static array $providers = [];
    private static array $reflections = [];

    private const GQL_SCALAR = 'scalar';
    private const GQL_ENUM = 'enum';
    private const GQL_TYPE = 'type';
    private const GQL_INPUT = 'input';
    private const GQL_UNION = 'union';
    private const GQL_INTERFACE = 'interface';

    /**
     * @see https://facebook.github.io/graphql/draft/#sec-Input-and-Output-Types
     */
    private const VALID_INPUT_TYPES = [self::GQL_SCALAR, self::GQL_ENUM, self::GQL_INPUT];
    private const VALID_OUTPUT_TYPES = [self::GQL_SCALAR, self::GQL_TYPE, self::GQL_INTERFACE, self::GQL_UNION, self::GQL_ENUM];

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function preParse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): void
    {
        $container->setParameter('overblog_graphql_types.classes_map', self::processFile($file, $container, $configs, true));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function parse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        return self::processFile($file, $container, $configs, false);
    }

    public static function finalize(ContainerBuilder $container): void
    {
        $parameter = 'overblog_graphql_types.interfaces_map';
        $value = $container->hasParameter($parameter) ? $container->getParameter($parameter) : [];
        foreach (self::$map->interfacesToArray() as $interface => $types) {
            if (!isset($value[$interface])) {
                $value[$interface] = [];
            }
            foreach ($types as $className => $typeName) {
                $value[$interface][$className] = $typeName;
            }
        }

        $container->setParameter('overblog_graphql_types.interfaces_map', $value);
    }

    /**
     * @internal
     */
    public static function reset(array $configs): void
    {
        self::$map = new ClassesTypesMap();
        self::$typeGuessers = [
            new DocBlockTypeGuesser(self::$map),
            new TypeHintTypeGuesser(self::$map),
            new DoctrineTypeGuesser(self::$map, $configs['doctrine']['types_mapping']),
        ];
        self::$providers = [];
        self::$reflections = [];
    }

    /**
     * Process a file.
     *
     * @throws InvalidArgumentException|ReflectionException|AnnotationException
     */
    private static function processFile(SplFileInfo $file, ContainerBuilder $container, array $configs, bool $preProcess): array
    {
        $container->addResource(new FileResource($file->getRealPath()));

        try {
            $className = $file->getBasename('.php');
            if (preg_match('#namespace (.+);#', file_get_contents($file->getRealPath()), $matches)) {
                $className = trim($matches[1]).'\\'.$className;
            }

            $gqlTypes = [];
            /** @phpstan-ignore-next-line */
            $reflectionClass = self::getClassReflection($className);

            foreach (static::getMetadatas($reflectionClass) as $classMetadata) {
                if ($classMetadata instanceof Meta) {
                    $gqlTypes = self::classMetadatasToGQLConfiguration(
                        $reflectionClass,
                        $classMetadata,
                        $configs,
                        $gqlTypes,
                        $preProcess
                    );
                }
            }

            return $preProcess ? self::$map->classesToArray() : $gqlTypes;
        } catch (ReflectionException $e) {
            return $gqlTypes;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Failed to parse GraphQL metadata from file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string,array>
     */
    private static function classMetadatasToGQLConfiguration(
        ReflectionClass $reflectionClass,
        Meta $classMetadata,
        array $configs,
        array $gqlTypes,
        bool $preProcess
    ): array {
        $gqlConfiguration = $gqlType = $gqlName = null;

        switch (true) {
            case $classMetadata instanceof Metadata\Type:
                $gqlType = self::GQL_TYPE;
                $gqlName = $classMetadata->name ?? $reflectionClass->getShortName();
                if (!$preProcess) {
                    $gqlConfiguration = self::typeMetadataToGQLConfiguration($reflectionClass, $classMetadata, $gqlName, $configs);

                    if ($classMetadata instanceof Metadata\Relay\Connection) {
                        if (!$reflectionClass->implementsInterface(ConnectionInterface::class)) {
                            throw new InvalidArgumentException(sprintf('The metadata %s on class "%s" can only be used on class implementing the ConnectionInterface.', self::formatMetadata('Connection'), $reflectionClass->getName()));
                        }

                        if (!(isset($classMetadata->edge) xor isset($classMetadata->node))) {
                            throw new InvalidArgumentException(sprintf('The metadata %s on class "%s" is invalid. You must define either the "edge" OR the "node" attribute, but not both.', self::formatMetadata('Connection'), $reflectionClass->getName()));
                        }

                        $edgeType = $classMetadata->edge ?? false;
                        if (!$edgeType) {
                            $edgeType = $gqlName.'Edge';
                            $gqlTypes[$edgeType] = [
                                'type' => 'object',
                                'config' => [
                                    'builders' => [
                                        ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $classMetadata->node]],
                                    ],
                                ],
                            ];
                        }

                        if (!isset($gqlConfiguration['config']['builders'])) {
                            $gqlConfiguration['config']['builders'] = [];
                        }

                        array_unshift($gqlConfiguration['config']['builders'], ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => $edgeType]]);
                    }

                    $interfaces = $gqlConfiguration['config']['interfaces'] ?? [];
                    foreach ($interfaces as $interface) {
                        self::$map->addInterfaceType($interface, $gqlName, $reflectionClass->getName());
                    }
                }
                break;

            case $classMetadata instanceof Metadata\Input:
                $gqlType = self::GQL_INPUT;
                $gqlName = $classMetadata->name ?? self::suffixName($reflectionClass->getShortName(), 'Input');
                if (!$preProcess) {
                    $gqlConfiguration = self::inputMetadataToGQLConfiguration($reflectionClass, $classMetadata);
                }
                break;

            case $classMetadata instanceof Metadata\Scalar:
                $gqlType = self::GQL_SCALAR;
                if (!$preProcess) {
                    $gqlConfiguration = self::scalarMetadataToGQLConfiguration($reflectionClass, $classMetadata);
                }
                break;

            case $classMetadata instanceof Metadata\Enum:
                $gqlType = self::GQL_ENUM;
                if (!$preProcess) {
                    $gqlConfiguration = self::enumMetadataToGQLConfiguration($reflectionClass, $classMetadata);
                }
                break;

            case $classMetadata instanceof Metadata\Union:
                $gqlType = self::GQL_UNION;
                if (!$preProcess) {
                    $gqlConfiguration = self::unionMetadataToGQLConfiguration($reflectionClass, $classMetadata);
                }
                break;

            case $classMetadata instanceof Metadata\TypeInterface:
                $gqlType = self::GQL_INTERFACE;
                if (!$preProcess) {
                    $gqlName = !empty($classMetadata->name) ? $classMetadata->name : $reflectionClass->getShortName();
                    $gqlConfiguration = self::typeInterfaceMetadataToGQLConfiguration($reflectionClass, $classMetadata, $gqlName);
                }
                break;

            case $classMetadata instanceof Metadata\Provider:
                if ($preProcess) {
                    self::$providers[] = ['reflectionClass' => $reflectionClass, 'metadata' => $classMetadata];
                }

                return [];
        }

        if (null !== $gqlType) {
            if (!$gqlName) {
                $gqlName = !empty($classMetadata->name) ? $classMetadata->name : $reflectionClass->getShortName();
            }

            if ($preProcess) {
                if (self::$map->hasType($gqlName)) {
                    throw new InvalidArgumentException(sprintf('The GraphQL type "%s" has already been registered in class "%s"', $gqlName, self::$map->getType($gqlName)['class']));
                }
                self::$map->addClassType($gqlName, $reflectionClass->getName(), $gqlType);
            } else {
                $gqlTypes = [$gqlName => $gqlConfiguration] + $gqlTypes;
            }
        }

        return $gqlTypes;
    }

    /**
     * @throws ReflectionException
     *
     * @phpstan-param class-string $className
     */
    private static function getClassReflection(string $className): ReflectionClass
    {
        self::$reflections[$className] ??= new ReflectionClass($className);

        return self::$reflections[$className];
    }

    private static function typeMetadataToGQLConfiguration(
        ReflectionClass $reflectionClass,
        Metadata\Type $classMetadata,
        string $gqlName,
        array $configs
    ): array {
        $isMutation = $isDefault = $isRoot = false;
        if (isset($configs['definitions']['schema'])) {
            $defaultSchemaName = isset($configs['definitions']['schema']['default']) ? 'default' : array_key_first($configs['definitions']['schema']);
            foreach ($configs['definitions']['schema'] as $schemaName => $schema) {
                $schemaQuery = $schema['query'] ?? null;
                $schemaMutation = $schema['mutation'] ?? null;
                $schemaSubscription = $schema['subscription'] ?? null;

                if ($gqlName === $schemaQuery) {
                    $isRoot = true;
                    if ($defaultSchemaName === $schemaName) {
                        $isDefault = true;
                    }
                } elseif ($gqlName === $schemaMutation) {
                    $isMutation = true;
                    $isRoot = true;
                    if ($defaultSchemaName === $schemaName) {
                        $isDefault = true;
                    }
                } elseif ($gqlName === $schemaSubscription) {
                    $isRoot = true;
                }
            }
        }

        $currentValue = $isRoot ? sprintf("service('%s')", self::formatNamespaceForExpression($reflectionClass->getName())) : 'value';

        $gqlConfiguration = self::graphQLTypeConfigFromAnnotation($reflectionClass, $classMetadata, $currentValue);

        $providerFields = self::getGraphQLFieldsFromProviders($reflectionClass, $isMutation ? Metadata\Mutation::class : Metadata\Query::class, $gqlName, $isDefault);
        $gqlConfiguration['config']['fields'] = array_merge($gqlConfiguration['config']['fields'], $providerFields);

        if ($classMetadata instanceof Metadata\Relay\Edge) {
            if (!$reflectionClass->implementsInterface(EdgeInterface::class)) {
                throw new InvalidArgumentException(sprintf('The metadata %s on class "%s" can only be used on class implementing the EdgeInterface.', self::formatMetadata('Edge'), $reflectionClass->getName()));
            }
            if (!isset($gqlConfiguration['config']['builders'])) {
                $gqlConfiguration['config']['builders'] = [];
            }
            array_unshift($gqlConfiguration['config']['builders'], ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $classMetadata->node]]);
        }

        return $gqlConfiguration;
    }

    /**
     * @return array{type: 'relay-mutation-payload'|'object', config: array}
     */
    private static function graphQLTypeConfigFromAnnotation(ReflectionClass $reflectionClass, Metadata\Type $typeAnnotation, string $currentValue): array
    {
        $typeConfiguration = [];
        $metadatas = static::getMetadatas($reflectionClass);

        $fieldsFromProperties = self::getGraphQLTypeFieldsFromAnnotations($reflectionClass, self::getClassProperties($reflectionClass), Metadata\Field::class, $currentValue);
        $fieldsFromMethods = self::getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods(), Metadata\Field::class, $currentValue);

        $typeConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $typeConfiguration = self::getDescriptionConfiguration($metadatas) + $typeConfiguration;

        if (!empty($typeAnnotation->interfaces)) {
            $typeConfiguration['interfaces'] = $typeAnnotation->interfaces;
        } else {
            $interfaces = array_keys(self::$map->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                ['class' => $interfaceClassName] = $configuration;

                $interfaceMetadata = self::getClassReflection($interfaceClassName);
                if ($interfaceMetadata->isInterface() && $reflectionClass->implementsInterface($interfaceMetadata->getName())) {
                    return true;
                }

                return $reflectionClass->isSubclassOf($interfaceClassName);
            }, self::GQL_INTERFACE));

            sort($interfaces);
            $typeConfiguration['interfaces'] = $interfaces;
        }

        if (isset($typeAnnotation->resolveField)) {
            $typeConfiguration['resolveField'] = self::formatExpression($typeAnnotation->resolveField);
        }

        $buildersAnnotations = self::getMetadataMatching($metadatas, Metadata\FieldsBuilder::class);
        if (!empty($buildersAnnotations)) {
            $typeConfiguration['builders'] = array_map(fn ($fieldsBuilderAnnotation) => ['builder' => $fieldsBuilderAnnotation->name, 'builderConfig' => $fieldsBuilderAnnotation->config], $buildersAnnotations);
        }

        if (isset($typeAnnotation->isTypeOf)) {
            $typeConfiguration['isTypeOf'] = $typeAnnotation->isTypeOf;
        }

        $publicMetadata = self::getFirstMetadataMatching($metadatas, Metadata\IsPublic::class);
        if (null !== $publicMetadata) {
            $typeConfiguration['fieldsDefaultPublic'] = self::formatExpression($publicMetadata->value);
        }

        $accessMetadata = self::getFirstMetadataMatching($metadatas, Metadata\Access::class);
        if (null !== $accessMetadata) {
            $typeConfiguration['fieldsDefaultAccess'] = self::formatExpression($accessMetadata->value);
        }

        return ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration];
    }

    /**
     * Create a GraphQL Interface type configuration from metadatas on properties.
     *
     * @return array{type: 'interface', config: array}
     */
    private static function typeInterfaceMetadataToGQLConfiguration(ReflectionClass $reflectionClass, Metadata\TypeInterface $interfaceAnnotation, string $gqlName): array
    {
        $interfaceConfiguration = [];

        $fieldsFromProperties = self::getGraphQLTypeFieldsFromAnnotations($reflectionClass, self::getClassProperties($reflectionClass));
        $fieldsFromMethods = self::getGraphQLTypeFieldsFromAnnotations($reflectionClass, $reflectionClass->getMethods());

        $interfaceConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $interfaceConfiguration = self::getDescriptionConfiguration(static::getMetadatas($reflectionClass)) + $interfaceConfiguration;

        if (isset($interfaceAnnotation->resolveType)) {
            $interfaceConfiguration['resolveType'] = self::formatExpression($interfaceAnnotation->resolveType);
        } else {
            // Try to use default interface resolver type
            $interfaceConfiguration['resolveType'] = self::formatExpression(sprintf("service('overblog_graphql.interface_type_resolver').resolveType('%s', value)", $gqlName));
        }

        return ['type' => 'interface', 'config' => $interfaceConfiguration];
    }

    /**
     * Create a GraphQL Input type configuration from metadatas on properties.
     *
     * @return array{type: 'relay-mutation-input'|'input-object', config: array}
     */
    private static function inputMetadataToGQLConfiguration(ReflectionClass $reflectionClass, Metadata\Input $inputAnnotation): array
    {
        $inputConfiguration = array_merge([
            'fields' => self::getGraphQLInputFieldsFromMetadatas($reflectionClass, self::getClassProperties($reflectionClass)),
        ], self::getDescriptionConfiguration(static::getMetadatas($reflectionClass), true));

        return ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration];
    }

    /**
     * Get a GraphQL scalar configuration from given scalar metadata.
     *
     * @return array{type: 'custom-scalar', config: array}
     */
    private static function scalarMetadataToGQLConfiguration(ReflectionClass $reflectionClass, Metadata\Scalar $scalarAnnotation): array
    {
        $scalarConfiguration = [];

        if (isset($scalarAnnotation->scalarType)) {
            $scalarConfiguration['scalarType'] = self::formatExpression($scalarAnnotation->scalarType);
        } else {
            $scalarConfiguration = [
                'serialize' => [$reflectionClass->getName(), 'serialize'],
                'parseValue' => [$reflectionClass->getName(), 'parseValue'],
                'parseLiteral' => [$reflectionClass->getName(), 'parseLiteral'],
            ];
        }

        $scalarConfiguration = self::getDescriptionConfiguration(static::getMetadatas($reflectionClass)) + $scalarConfiguration;

        return ['type' => 'custom-scalar', 'config' => $scalarConfiguration];
    }

    /**
     * Get a GraphQL Enum configuration from given enum metadata.
     *
     * @return array{type: 'enum', config: array}
     */
    private static function enumMetadataToGQLConfiguration(ReflectionClass $reflectionClass, Metadata\Enum $enumMetadata): array
    {
        $metadatas = static::getMetadatas($reflectionClass);
        $enumValues = self::getMetadataMatching($metadatas, Metadata\EnumValue::class);
        $isPhpEnum = PHP_VERSION_ID >= 80100 && $reflectionClass->isEnum();
        $values = [];

        foreach ($reflectionClass->getConstants() as $name => $value) {
            $reflectionConstant = new ReflectionClassConstant($reflectionClass->getName(), $name);
            $valueConfig = self::getDescriptionConfiguration(static::getMetadatas($reflectionConstant), true);

            $enumValueAnnotation = current(array_filter($enumValues, fn ($enumValueAnnotation) => $enumValueAnnotation->name === $name));
            $valueConfig['value'] = $isPhpEnum ? $value->name : $value;

            if (false !== $enumValueAnnotation) {
                if (isset($enumValueAnnotation->description)) {
                    $valueConfig['description'] = $enumValueAnnotation->description;
                }

                if (isset($enumValueAnnotation->deprecationReason)) {
                    $valueConfig['deprecationReason'] = $enumValueAnnotation->deprecationReason;
                }
            }

            $values[$name] = $valueConfig;
        }

        $enumConfiguration = ['values' => $values];
        $enumConfiguration = self::getDescriptionConfiguration(static::getMetadatas($reflectionClass)) + $enumConfiguration;
        if ($isPhpEnum) {
            $enumConfiguration['enumClass'] = $reflectionClass->getName();
        }

        return ['type' => 'enum', 'config' => $enumConfiguration];
    }

    /**
     * Get a GraphQL Union configuration from given union metadata.
     *
     * @return array{type: 'union', config: array}
     */
    private static function unionMetadataToGQLConfiguration(ReflectionClass $reflectionClass, Metadata\Union $unionMetadata): array
    {
        $unionConfiguration = [];
        if (!empty($unionMetadata->types)) {
            $unionConfiguration['types'] = $unionMetadata->types;
        } else {
            $types = array_keys(self::$map->searchClassesMapBy(function ($gqlType, $configuration) use ($reflectionClass) {
                $typeClassName = $configuration['class'];
                $typeMetadata = self::getClassReflection($typeClassName);

                if ($reflectionClass->isInterface() && $typeMetadata->implementsInterface($reflectionClass->getName())) {
                    return true;
                }

                return $typeMetadata->isSubclassOf($reflectionClass->getName());
            }, self::GQL_TYPE));
            sort($types);
            $unionConfiguration['types'] = $types;
        }

        $unionConfiguration = self::getDescriptionConfiguration(static::getMetadatas($reflectionClass)) + $unionConfiguration;

        if (isset($unionMetadata->resolveType)) {
            $unionConfiguration['resolveType'] = self::formatExpression($unionMetadata->resolveType);
        } else {
            if ($reflectionClass->hasMethod('resolveType')) {
                $method = $reflectionClass->getMethod('resolveType');
                if ($method->isStatic() && $method->isPublic()) {
                    $unionConfiguration['resolveType'] = self::formatExpression(sprintf("@=call('%s::%s', [service('overblog_graphql.type_resolver'), value], true)", self::formatNamespaceForExpression($reflectionClass->getName()), 'resolveType'));
                } else {
                    throw new InvalidArgumentException(sprintf('The "resolveType()" method on class must be static and public. Or you must define a "resolveType" attribute on the %s metadata.', self::formatMetadata('Union')));
                }
            } else {
                throw new InvalidArgumentException(sprintf('The metadata %s has no "resolveType" attribute and the related class has no "resolveType()" public static method. You need to define of them.', self::formatMetadata('Union')));
            }
        }

        return ['type' => 'union', 'config' => $unionConfiguration];
    }

    /**
     * @phpstan-param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @return array<string,array>
     *
     * @throws AnnotationException
     */
    private static function getTypeFieldConfigurationFromReflector(ReflectionClass $reflectionClass, Reflector $reflector, string $fieldMetadataName, string $currentValue = 'value'): array
    {
        /** @var ReflectionProperty|ReflectionMethod $reflector */
        $metadatas = static::getMetadatas($reflector);

        $fieldMetadata = self::getFirstMetadataMatching($metadatas, $fieldMetadataName);
        $accessMetadata = self::getFirstMetadataMatching($metadatas, Metadata\Access::class);
        $publicMetadata = self::getFirstMetadataMatching($metadatas, Metadata\IsPublic::class);

        if (null === $fieldMetadata) {
            if (null !== $accessMetadata || null !== $publicMetadata) {
                throw new InvalidArgumentException(sprintf('The metadatas %s and/or %s defined on "%s" are only usable in addition of metadata %s', self::formatMetadata('Access'), self::formatMetadata('Visible'), $reflector->getName(), self::formatMetadata('Field')));
            }

            return [];
        }

        if ($reflector instanceof ReflectionMethod && !$reflector->isPublic()) {
            throw new InvalidArgumentException(sprintf('The metadata %s can only be applied to public method. The method "%s" is not public.', self::formatMetadata('Field'), $reflector->getName()));
        }

        $fieldName = $reflector->getName();
        $fieldConfiguration = [];

        if (isset($fieldMetadata->type)) {
            $fieldConfiguration['type'] = $fieldMetadata->type;
        }

        $fieldConfiguration = self::getDescriptionConfiguration($metadatas, true) + $fieldConfiguration;

        $args = [];

        /** @var Metadata\Arg[] $argAnnotations */
        $argAnnotations = self::getMetadataMatching($metadatas, Metadata\Arg::class);

        foreach ($argAnnotations as $arg) {
            $args[$arg->name] = ['type' => $arg->type];

            if (isset($arg->description)) {
                $args[$arg->name]['description'] = $arg->description;
            }

            if (isset($arg->defaultValue)) {
                $args[$arg->name]['defaultValue'] = $arg->defaultValue;
            } elseif (isset($arg->default)) {
                trigger_deprecation('overblog/graphql-bundle', '1.3', 'The "default" attribute on @GQL\Arg or #GQL\Arg is deprecated, use "defaultValue" instead.');
                $args[$arg->name]['defaultValue'] = $arg->default;
            }
        }

        if ($reflector instanceof ReflectionMethod) {
            $args = self::guessArgs($reflectionClass, $reflector, $args);
        }

        if (!empty($args)) {
            $fieldConfiguration['args'] = $args;
        }

        $fieldName = $fieldMetadata->name ?? $fieldName;

        if (isset($fieldMetadata->resolve)) {
            $fieldConfiguration['resolve'] = self::formatExpression($fieldMetadata->resolve);
        } else {
            if ($reflector instanceof ReflectionMethod) {
                $fieldConfiguration['resolve'] = self::formatExpression(sprintf('call(%s.%s, %s)', $currentValue, $reflector->getName(), self::formatArgsForExpression($args)));
            } else {
                if ($fieldName !== $reflector->getName() || 'value' !== $currentValue) {
                    $fieldConfiguration['resolve'] = self::formatExpression(sprintf('%s.%s', $currentValue, $reflector->getName()));
                }
            }
        }

        $argsBuilder = self::getFirstMetadataMatching($metadatas, Metadata\ArgsBuilder::class);
        if ($argsBuilder) {
            $fieldConfiguration['argsBuilder'] = ['builder' => $argsBuilder->name, 'config' => $argsBuilder->config];
        }
        $fieldBuilder = self::getFirstMetadataMatching($metadatas, Metadata\FieldBuilder::class);
        if ($fieldBuilder) {
            $fieldConfiguration['builder'] = $fieldBuilder->name;
            $fieldConfiguration['builderConfig'] = $fieldBuilder->config;
        } else {
            if (!isset($fieldMetadata->type)) {
                try {
                    $fieldConfiguration['type'] = self::guessType($reflectionClass, $reflector, self::VALID_OUTPUT_TYPES);
                } catch (TypeGuessingException $e) {
                    $error = sprintf('The attribute "type" on %s is missing on %s "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", static::formatMetadata($fieldMetadataName), $reflector instanceof ReflectionProperty ? 'property' : 'method', $reflector->getName(), $e->getMessage());

                    throw new InvalidArgumentException($error);
                }
            }
        }

        if ($accessMetadata) {
            $fieldConfiguration['access'] = self::formatExpression($accessMetadata->value);
        }

        if ($publicMetadata) {
            $fieldConfiguration['public'] = self::formatExpression($publicMetadata->value);
        }

        if (isset($fieldMetadata->complexity)) {
            $fieldConfiguration['complexity'] = self::formatExpression($fieldMetadata->complexity);
        }

        return [$fieldName => $fieldConfiguration];
    }

    /**
     * Create GraphQL input fields configuration based on metadatas.
     *
     * @param ReflectionProperty[] $reflectors
     *
     * @return array<string,array>
     *
     * @throws AnnotationException
     */
    private static function getGraphQLInputFieldsFromMetadatas(ReflectionClass $reflectionClass, array $reflectors): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $metadatas = static::getMetadatas($reflector);

            /** @var Metadata\Field|null $fieldMetadata */
            $fieldMetadata = self::getFirstMetadataMatching($metadatas, Metadata\Field::class);

            // No field metadata found
            if (null === $fieldMetadata) {
                continue;
            }

            // Ignore field with resolver when the type is an Input
            if (isset($fieldMetadata->resolve)) {
                continue;
            }

            $fieldName = $reflector->getName();
            if (isset($fieldMetadata->type)) {
                $fieldType = $fieldMetadata->type;
            } else {
                try {
                    $fieldType = self::guessType($reflectionClass, $reflector, self::VALID_INPUT_TYPES);
                } catch (TypeGuessingException $e) {
                    throw new InvalidArgumentException(sprintf('The attribute "type" on %s is missing on property "%s" and cannot be auto-guessed from the following type guessers:'."\n%s\n", self::formatMetadata(Metadata\Field::class), $reflector->getName(), $e->getMessage()));
                }
            }
            $fieldConfiguration = [];
            if ($fieldType) {
                // Resolve a PHP class from a GraphQL type
                $resolvedType = self::$map->getType($fieldType);
                // We found a type but it is not allowed
                if (null !== $resolvedType && !in_array($resolvedType['type'], self::VALID_INPUT_TYPES)) {
                    throw new InvalidArgumentException(sprintf('The type "%s" on "%s" is a "%s" not valid on an Input %s. Only Input, Scalar and Enum are allowed.', $fieldType, $reflector->getName(), $resolvedType['type'], self::formatMetadata('Field')));
                }

                $fieldConfiguration['type'] = $fieldType;
            }

            if ($fieldMetadata instanceof InputField && null !== $fieldMetadata->defaultValue) {
                $fieldConfiguration['defaultValue'] = $fieldMetadata->defaultValue;
            } elseif ($reflector->hasDefaultValue() && null !== $reflector->getDefaultValue()) {
                $fieldConfiguration['defaultValue'] = $reflector->getDefaultValue();
            }

            $fieldConfiguration = array_merge(self::getDescriptionConfiguration($metadatas, true), $fieldConfiguration);
            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }

    /**
     * Create GraphQL type fields configuration based on metadatas.
     *
     * @phpstan-param class-string<Metadata\Field> $fieldMetadataName
     *
     * @param ReflectionProperty[]|ReflectionMethod[] $reflectors
     *
     * @throws AnnotationException
     */
    private static function getGraphQLTypeFieldsFromAnnotations(ReflectionClass $reflectionClass, array $reflectors, string $fieldMetadataName = Metadata\Field::class, string $currentValue = 'value'): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $fields = array_merge($fields, self::getTypeFieldConfigurationFromReflector($reflectionClass, $reflector, $fieldMetadataName, $currentValue));
        }

        return $fields;
    }

    /**
     * @phpstan-param class-string<Metadata\Query|Metadata\Mutation> $expectedMetadata
     *
     * Return fields config from Provider methods.
     * Loop through configured provider and extract fields targeting the targetType.
     *
     * @return array<string,array>
     */
    private static function getGraphQLFieldsFromProviders(ReflectionClass $reflectionClass, string $expectedMetadata, string $targetType, bool $isDefaultTarget = false): array
    {
        $fields = [];
        foreach (self::$providers as ['reflectionClass' => $providerReflection, 'metadata' => $providerMetadata]) {
            $defaultAccessAnnotation = self::getFirstMetadataMatching(static::getMetadatas($providerReflection), Metadata\Access::class);
            $defaultIsPublicAnnotation = self::getFirstMetadataMatching(static::getMetadatas($providerReflection), Metadata\IsPublic::class);

            $defaultAccess = $defaultAccessAnnotation ? self::formatExpression($defaultAccessAnnotation->value) : false;
            $defaultIsPublic = $defaultIsPublicAnnotation ? self::formatExpression($defaultIsPublicAnnotation->value) : false;

            $methods = [];
            // First found the methods matching the targeted type
            foreach ($providerReflection->getMethods() as $method) {
                $metadatas = static::getMetadatas($method);

                $metadata = self::getFirstMetadataMatching($metadatas, [Metadata\Mutation::class, Metadata\Query::class]);
                if (null === $metadata) {
                    continue;
                }

                // TODO: Remove old property check in 1.1
                $metadataTargets = $metadata->targetTypes ?? null;

                if (null === $metadataTargets) {
                    if ($metadata instanceof Metadata\Mutation && isset($providerMetadata->targetMutationTypes)) {
                        $metadataTargets = $providerMetadata->targetMutationTypes;
                    } elseif ($metadata instanceof Metadata\Query && isset($providerMetadata->targetQueryTypes)) {
                        $metadataTargets = $providerMetadata->targetQueryTypes;
                    }
                }

                if (null === $metadataTargets) {
                    if ($isDefaultTarget) {
                        $metadataTargets = [$targetType];
                        if (!$metadata instanceof $expectedMetadata) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                if (!in_array($targetType, $metadataTargets)) {
                    continue;
                }

                if (!$metadata instanceof $expectedMetadata) {
                    if (Metadata\Mutation::class === $expectedMetadata) {
                        $message = sprintf('The provider "%s" try to add a query field on type "%s" (through %s on method "%s") but "%s" is a mutation.', $providerReflection->getName(), $targetType, self::formatMetadata('Query'), $method->getName(), $targetType);
                    } else {
                        $message = sprintf('The provider "%s" try to add a mutation on type "%s" (through %s on method "%s") but "%s" is not a mutation.', $providerReflection->getName(), $targetType, self::formatMetadata('Mutation'), $method->getName(), $targetType);
                    }

                    throw new InvalidArgumentException($message);
                }
                $methods[$method->getName()] = $method;
            }

            $currentValue = sprintf("service('%s')", self::formatNamespaceForExpression($providerReflection->getName()));
            $providerFields = self::getGraphQLTypeFieldsFromAnnotations($reflectionClass, $methods, $expectedMetadata, $currentValue);
            foreach ($providerFields as $fieldName => $fieldConfig) {
                if (isset($providerMetadata->prefix)) {
                    $fieldName = sprintf('%s%s', $providerMetadata->prefix, $fieldName);
                }

                if ($defaultAccess && !isset($fieldConfig['access'])) {
                    $fieldConfig['access'] = $defaultAccess;
                }

                if ($defaultIsPublic && !isset($fieldConfig['public'])) {
                    $fieldConfig['public'] = $defaultIsPublic;
                }

                $fields[$fieldName] = $fieldConfig;
            }
        }

        return $fields;
    }

    /**
     * Get the config for description & deprecation reason.
     *
     * @return array<'description'|'deprecationReason',string>
     */
    private static function getDescriptionConfiguration(array $metadatas, bool $withDeprecation = false): array
    {
        $config = [];
        $descriptionAnnotation = self::getFirstMetadataMatching($metadatas, Metadata\Description::class);
        if (null !== $descriptionAnnotation) {
            $config['description'] = $descriptionAnnotation->value;
        }

        if ($withDeprecation) {
            $deprecatedAnnotation = self::getFirstMetadataMatching($metadatas, Metadata\Deprecated::class);
            if (null !== $deprecatedAnnotation) {
                $config['deprecationReason'] = $deprecatedAnnotation->value;
            }
        }

        return $config;
    }

    /**
     * Format an array of args to a list of arguments in an expression.
     */
    private static function formatArgsForExpression(array $args): string
    {
        $mapping = [];
        foreach ($args as $name => $config) {
            $mapping[] = sprintf('%s: "%s"', $name, $config['type']);
        }

        return sprintf('arguments({%s}, args)', implode(', ', $mapping));
    }

    /**
     * Format a namespace to be used in an expression (double escape).
     */
    private static function formatNamespaceForExpression(string $namespace): string
    {
        return str_replace('\\', '\\\\', $namespace);
    }

    /**
     * Get the first metadata matching given class.
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T>|class-string<T>[] $metadataClasses
     *
     * @phpstan-return T|null
     *
     * @return object|null
     */
    private static function getFirstMetadataMatching(array $metadatas, $metadataClasses)
    {
        $metas = self::getMetadataMatching($metadatas, $metadataClasses);

        return array_shift($metas);
    }

    /**
     * Return the metadata matching given class
     *
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T>|class-string<T>[] $metadataClasses
     *
     * @return array
     */
    private static function getMetadataMatching(array $metadatas, $metadataClasses)
    {
        if (is_string($metadataClasses)) {
            $metadataClasses = [$metadataClasses];
        }

        return array_values(array_filter($metadatas, function ($metadata) use ($metadataClasses) {
            foreach ($metadataClasses as $metadataClass) {
                if ($metadata instanceof $metadataClass) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Format an expression (ie. add "@=" if not set).
     */
    private static function formatExpression(string $expression): string
    {
        return '@=' === substr($expression, 0, 2) ? $expression : sprintf('@=%s', $expression);
    }

    /**
     * Suffix a name if it is not already.
     */
    private static function suffixName(string $name, string $suffix): string
    {
        return substr($name, -strlen($suffix)) === $suffix ? $name : sprintf('%s%s', $name, $suffix);
    }

    /**
     * Try to guess a GraphQL type using configured type guessers
     *
     * @throws RuntimeException
     */
    private static function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): string
    {
        $errors = [];
        foreach (self::$typeGuessers as $typeGuesser) {
            if (!$typeGuesser->supports($reflector)) {
                continue;
            }
            try {
                $type = $typeGuesser->guessType($reflectionClass, $reflector, $filterGraphQLTypes);

                return $type;
            } catch (TypeGuessingException $exception) {
                $errors[] = sprintf('[%s] %s', $typeGuesser->getName(), $exception->getMessage());
            }
        }

        throw new TypeGuessingException(implode("\n", $errors));
    }

    /**
     * Transform a method arguments from reflection to a list of GraphQL argument.
     */
    private static function guessArgs(
        ReflectionClass $reflectionClass,
        ReflectionMethod $method,
        array $arguments,
    ): array {
        foreach ($method->getParameters() as $index => $parameter) {
            if (array_key_exists($parameter->getName(), $arguments)) {
                continue;
            }

            try {
                $gqlType = self::guessType($reflectionClass, $parameter, self::VALID_INPUT_TYPES);
            } catch (TypeGuessingException $exception) {
                throw new InvalidArgumentException(sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed from the following type guessers:'."\n%s\n", $index + 1, $parameter->getName(), $method->getName(), $exception->getMessage()));
            }

            $argumentConfig = [];
            if ($parameter->isDefaultValueAvailable()) {
                $argumentConfig['defaultValue'] = $parameter->getDefaultValue();
            }

            $argumentConfig['type'] = $gqlType;

            $arguments[$parameter->getName()] = $argumentConfig;
        }

        return $arguments;
    }

    /**
     * @return ReflectionProperty[]
     */
    private static function getClassProperties(ReflectionClass $reflectionClass): array
    {
        $properties = [];
        do {
            foreach ($reflectionClass->getProperties() as $property) {
                if (isset($properties[$property->getName()])) {
                    continue;
                }
                $properties[$property->getName()] = $property;
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return $properties;
    }

    protected static function formatMetadata(string $className): string
    {
        return sprintf(static::METADATA_FORMAT, str_replace(self::ANNOTATION_NAMESPACE, '', $className));
    }

    abstract protected static function getMetadatas(Reflector $reflector): array;
}
