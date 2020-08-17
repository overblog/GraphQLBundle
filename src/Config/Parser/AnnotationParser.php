<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Config\Parser\Annotation\GraphClass;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
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
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

class AnnotationParser implements PreParserInterface
{
    private static array $classesMap = [];
    private static array $providers = [];
    private static array $doctrineMapping = [];
    private static array $graphClassCache = [];

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

    /**
     * @internal
     */
    public static function reset(): void
    {
        self::$classesMap = [];
        self::$providers = [];
        self::$graphClassCache = [];
    }

    /**
     * Process a file.
     *
     * @throws InvalidArgumentException|ReflectionException|AnnotationException
     */
    private static function processFile(SplFileInfo $file, ContainerBuilder $container, array $configs, bool $preProcess): array
    {
        self::$doctrineMapping = $configs['doctrine']['types_mapping'];
        $container->addResource(new FileResource($file->getRealPath()));

        try {
            $className = $file->getBasename('.php');
            if (preg_match('#namespace (.+);#', file_get_contents($file->getRealPath()), $matches)) {
                $className = trim($matches[1]).'\\'.$className;
            }

            $gqlTypes = [];
            $graphClass = self::getGraphClass($className);

            foreach ($graphClass->getAnnotations() as $classAnnotation) {
                $gqlTypes = self::classAnnotationsToGQLConfiguration(
                    $graphClass,
                    $classAnnotation,
                    $configs,
                    $gqlTypes,
                    $preProcess
                );
            }

            return $preProcess ? self::$classesMap : $gqlTypes;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Failed to parse GraphQL annotations from file "%s".', $file), $e->getCode(), $e);
        }
    }

    private static function classAnnotationsToGQLConfiguration(
        GraphClass $graphClass,
        object $classAnnotation,
        array $configs,
        array $gqlTypes,
        bool $preProcess
    ): array {
        $gqlConfiguration = $gqlType = $gqlName = null;

        switch (true) {
            case $classAnnotation instanceof GQL\Type:
                $gqlType = self::GQL_TYPE;
                $gqlName = $classAnnotation->name ?? $graphClass->getShortName();
                if (!$preProcess) {
                    $gqlConfiguration = self::typeAnnotationToGQLConfiguration($graphClass, $classAnnotation, $gqlName, $configs);

                    if ($classAnnotation instanceof GQL\Relay\Connection) {
                        if (!$graphClass->implementsInterface(ConnectionInterface::class)) {
                            throw new InvalidArgumentException(sprintf('The annotation @Connection on class "%s" can only be used on class implementing the ConnectionInterface.', $graphClass->getName()));
                        }

                        if (!(isset($classAnnotation->edge) xor isset($classAnnotation->node))) {
                            throw new InvalidArgumentException(sprintf('The annotation @Connection on class "%s" is invalid. You must define either the "edge" OR the "node" attribute, but not both.', $graphClass->getName()));
                        }

                        $edgeType = $classAnnotation->edge ?? false;
                        if (!$edgeType) {
                            $edgeType = $gqlName.'Edge';
                            $gqlTypes[$edgeType] = [
                                'type' => 'object',
                                'config' => [
                                    'builders' => [
                                        ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $classAnnotation->node]],
                                    ],
                                ],
                            ];
                        }

                        if (!isset($gqlConfiguration['config']['builders'])) {
                            $gqlConfiguration['config']['builders'] = [];
                        }

                        array_unshift($gqlConfiguration['config']['builders'], ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => $edgeType]]);
                    }
                }
                break;

            case $classAnnotation instanceof GQL\Input:
                $gqlType = self::GQL_INPUT;
                $gqlName = $classAnnotation->name ?? self::suffixName($graphClass->getShortName(), 'Input');
                if (!$preProcess) {
                    $gqlConfiguration = self::inputAnnotationToGQLConfiguration($graphClass, $classAnnotation);
                }
                break;

            case $classAnnotation instanceof GQL\Scalar:
                $gqlType = self::GQL_SCALAR;
                if (!$preProcess) {
                    $gqlConfiguration = self::scalarAnnotationToGQLConfiguration($graphClass, $classAnnotation);
                }
                break;

            case $classAnnotation instanceof GQL\Enum:
                $gqlType = self::GQL_ENUM;
                if (!$preProcess) {
                    $gqlConfiguration = self::enumAnnotationToGQLConfiguration($graphClass, $classAnnotation);
                }
                break;

            case $classAnnotation instanceof GQL\Union:
                $gqlType = self::GQL_UNION;
                if (!$preProcess) {
                    $gqlConfiguration = self::unionAnnotationToGQLConfiguration($graphClass, $classAnnotation);
                }
                break;

            case $classAnnotation instanceof GQL\TypeInterface:
                $gqlType = self::GQL_INTERFACE;
                if (!$preProcess) {
                    $gqlConfiguration = self::typeInterfaceAnnotationToGQLConfiguration($graphClass, $classAnnotation);
                }
                break;

            case $classAnnotation instanceof GQL\Provider:
                if ($preProcess) {
                    self::$providers[] = ['metadata' => $graphClass, 'annotation' => $classAnnotation];
                }

                return [];
        }

        if (null !== $gqlType) {
            if (!$gqlName) {
                $gqlName = isset($classAnnotation->name) ? $classAnnotation->name : $graphClass->getShortName();
            }

            if ($preProcess) {
                if (isset(self::$classesMap[$gqlName])) {
                    throw new InvalidArgumentException(sprintf('The GraphQL type "%s" has already been registered in class "%s"', $gqlName, self::$classesMap[$gqlName]['class']));
                }
                self::$classesMap[$gqlName] = ['type' => $gqlType, 'class' => $graphClass->getName()];
            } else {
                $gqlTypes = [$gqlName => $gqlConfiguration] + $gqlTypes;
            }
        }

        return $gqlTypes;
    }

    /**
     * @throws ReflectionException
     */
    private static function getGraphClass(string $className): GraphClass
    {
        self::$graphClassCache[$className] ??= new GraphClass($className);

        return self::$graphClassCache[$className];
    }

    private static function typeAnnotationToGQLConfiguration(
        GraphClass $graphClass,
        GQL\Type $classAnnotation,
        string $gqlName,
        array $configs
    ): array {
        $isMutation = $isDefault = $isRoot = false;
        if (isset($configs['definitions']['schema'])) {
            $defaultSchemaName = isset($configs['definitions']['schema']['default']) ? 'default' : array_key_first($configs['definitions']['schema']);
            foreach ($configs['definitions']['schema'] as $schemaName => $schema) {
                $schemaQuery = $schema['query'] ?? null;
                $schemaMutation = $schema['mutation'] ?? null;

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
                }
            }
        }

        $currentValue = $isRoot ? sprintf("service('%s')", self::formatNamespaceForExpression($graphClass->getName())) : 'value';

        $gqlConfiguration = self::graphQLTypeConfigFromAnnotation($graphClass, $classAnnotation, $currentValue);

        $providerFields = self::getGraphQLFieldsFromProviders($graphClass, $isMutation ? GQL\Mutation::class : GQL\Query::class, $gqlName, $isDefault);
        $gqlConfiguration['config']['fields'] = array_merge($gqlConfiguration['config']['fields'], $providerFields);

        if ($classAnnotation instanceof GQL\Relay\Edge) {
            if (!$graphClass->implementsInterface(EdgeInterface::class)) {
                throw new InvalidArgumentException(sprintf('The annotation @Edge on class "%s" can only be used on class implementing the EdgeInterface.', $graphClass->getName()));
            }
            if (!isset($gqlConfiguration['config']['builders'])) {
                $gqlConfiguration['config']['builders'] = [];
            }
            array_unshift($gqlConfiguration['config']['builders'], ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $classAnnotation->node]]);
        }

        return $gqlConfiguration;
    }

    private static function graphQLTypeConfigFromAnnotation(GraphClass $graphClass, GQL\Type $typeAnnotation, string $currentValue): array
    {
        $typeConfiguration = [];
        $fieldsFromProperties = self::getGraphQLTypeFieldsFromAnnotations($graphClass, $graphClass->getPropertiesExtended(), GQL\Field::class, $currentValue);
        $fieldsFromMethods = self::getGraphQLTypeFieldsFromAnnotations($graphClass, $graphClass->getMethods(), GQL\Field::class, $currentValue);

        $typeConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $typeConfiguration = self::getDescriptionConfiguration($graphClass->getAnnotations()) + $typeConfiguration;

        if (isset($typeAnnotation->interfaces)) {
            $typeConfiguration['interfaces'] = $typeAnnotation->interfaces;
        } else {
            $interfaces = array_keys(self::searchClassesMapBy(function ($gqlType, $configuration) use ($graphClass) {
                ['class' => $interfaceClassName] = $configuration;

                $interfaceMetadata = self::getGraphClass($interfaceClassName);
                if ($interfaceMetadata->isInterface() && $graphClass->implementsInterface($interfaceMetadata->getName())) {
                    return true;
                }

                return $graphClass->isSubclassOf($interfaceClassName);
            }, self::GQL_INTERFACE));

            sort($interfaces);
            $typeConfiguration['interfaces'] = $interfaces;
        }

        if (isset($typeAnnotation->resolveField)) {
            $typeConfiguration['resolveField'] = self::formatExpression($typeAnnotation->resolveField);
        }

        if (isset($typeAnnotation->builders) && !empty($typeAnnotation->builders)) {
            $typeConfiguration['builders'] = array_map(function ($fieldsBuilderAnnotation) {
                return ['builder' => $fieldsBuilderAnnotation->builder, 'builderConfig' => $fieldsBuilderAnnotation->builderConfig];
            }, $typeAnnotation->builders);
        }

        if (isset($typeAnnotation->isTypeOf)) {
            $typeConfiguration['isTypeOf'] = $typeAnnotation->isTypeOf;
        }

        $publicAnnotation = self::getFirstAnnotationMatching($graphClass->getAnnotations(), GQL\IsPublic::class);
        if (null !== $publicAnnotation) {
            $typeConfiguration['fieldsDefaultPublic'] = self::formatExpression($publicAnnotation->value);
        }

        $accessAnnotation = self::getFirstAnnotationMatching($graphClass->getAnnotations(), GQL\Access::class);
        if (null !== $accessAnnotation) {
            $typeConfiguration['fieldsDefaultAccess'] = self::formatExpression($accessAnnotation->value);
        }

        return ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration];
    }

    /**
     * Create a GraphQL Interface type configuration from annotations on properties.
     */
    private static function typeInterfaceAnnotationToGQLConfiguration(GraphClass $graphClass, GQL\TypeInterface $interfaceAnnotation): array
    {
        $interfaceConfiguration = [];

        $fieldsFromProperties = self::getGraphQLTypeFieldsFromAnnotations($graphClass, $graphClass->getPropertiesExtended());
        $fieldsFromMethods = self::getGraphQLTypeFieldsFromAnnotations($graphClass, $graphClass->getMethods());

        $interfaceConfiguration['fields'] = array_merge($fieldsFromProperties, $fieldsFromMethods);
        $interfaceConfiguration = self::getDescriptionConfiguration($graphClass->getAnnotations()) + $interfaceConfiguration;

        $interfaceConfiguration['resolveType'] = self::formatExpression($interfaceAnnotation->resolveType);

        return ['type' => 'interface', 'config' => $interfaceConfiguration];
    }

    /**
     * Create a GraphQL Input type configuration from annotations on properties.
     */
    private static function inputAnnotationToGQLConfiguration(GraphClass $graphClass, GQL\Input $inputAnnotation): array
    {
        $inputConfiguration = array_merge([
            'fields' => self::getGraphQLInputFieldsFromAnnotations($graphClass, $graphClass->getPropertiesExtended()),
        ], self::getDescriptionConfiguration($graphClass->getAnnotations()));

        return ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration];
    }

    /**
     * Get a GraphQL scalar configuration from given scalar annotation.
     */
    private static function scalarAnnotationToGQLConfiguration(GraphClass $graphClass, GQL\Scalar $scalarAnnotation): array
    {
        $scalarConfiguration = [];

        if (isset($scalarAnnotation->scalarType)) {
            $scalarConfiguration['scalarType'] = self::formatExpression($scalarAnnotation->scalarType);
        } else {
            $scalarConfiguration = [
                'serialize' => [$graphClass->getName(), 'serialize'],
                'parseValue' => [$graphClass->getName(), 'parseValue'],
                'parseLiteral' => [$graphClass->getName(), 'parseLiteral'],
            ];
        }

        $scalarConfiguration = self::getDescriptionConfiguration($graphClass->getAnnotations()) + $scalarConfiguration;

        return ['type' => 'custom-scalar', 'config' => $scalarConfiguration];
    }

    /**
     * Get a GraphQL Enum configuration from given enum annotation.
     */
    private static function enumAnnotationToGQLConfiguration(GraphClass $graphClass, GQL\Enum $enumAnnotation): array
    {
        $enumValues = $enumAnnotation->values ? $enumAnnotation->values : [];

        $values = [];

        foreach ($graphClass->getConstants() as $name => $value) {
            $valueAnnotation = current(array_filter($enumValues, fn ($enumValueAnnotation) => $enumValueAnnotation->name == $name));
            $valueConfig = [];
            $valueConfig['value'] = $value;

            if ($valueAnnotation && isset($valueAnnotation->description)) {
                $valueConfig['description'] = $valueAnnotation->description;
            }

            if ($valueAnnotation && isset($valueAnnotation->deprecationReason)) {
                $valueConfig['deprecationReason'] = $valueAnnotation->deprecationReason;
            }

            $values[$name] = $valueConfig;
        }

        $enumConfiguration = ['values' => $values];
        $enumConfiguration = self::getDescriptionConfiguration($graphClass->getAnnotations()) + $enumConfiguration;

        return ['type' => 'enum', 'config' => $enumConfiguration];
    }

    /**
     * Get a GraphQL Union configuration from given union annotation.
     */
    private static function unionAnnotationToGQLConfiguration(GraphClass $graphClass, GQL\Union $unionAnnotation): array
    {
        $unionConfiguration = [];
        if (isset($unionAnnotation->types)) {
            $unionConfiguration['types'] = $unionAnnotation->types;
        } else {
            $types = array_keys(self::searchClassesMapBy(function ($gqlType, $configuration) use ($graphClass) {
                $typeClassName = $configuration['class'];
                $typeMetadata = self::getGraphClass($typeClassName);

                if ($graphClass->isInterface() && $typeMetadata->implementsInterface($graphClass->getName())) {
                    return true;
                }

                return $typeMetadata->isSubclassOf($graphClass->getName());
            }, self::GQL_TYPE));
            sort($types);
            $unionConfiguration['types'] = $types;
        }

        $unionConfiguration = self::getDescriptionConfiguration($graphClass->getAnnotations()) + $unionConfiguration;

        if (isset($unionAnnotation->resolveType)) {
            $unionConfiguration['resolveType'] = self::formatExpression($unionAnnotation->resolveType);
        } else {
            if ($graphClass->hasMethod('resolveType')) {
                $method = $graphClass->getMethod('resolveType');
                if ($method->isStatic() && $method->isPublic()) {
                    $unionConfiguration['resolveType'] = self::formatExpression(sprintf("@=call('%s::%s', [service('overblog_graphql.type_resolver'), value], true)", self::formatNamespaceForExpression($graphClass->getName()), 'resolveType'));
                } else {
                    throw new InvalidArgumentException(sprintf('The "resolveType()" method on class must be static and public. Or you must define a "resolveType" attribute on the @Union annotation.'));
                }
            } else {
                throw new InvalidArgumentException(sprintf('The annotation @Union has no "resolveType" attribute and the related class has no "resolveType()" public static method. You need to define of them.'));
            }
        }

        return ['type' => 'union', 'config' => $unionConfiguration];
    }

    /**
     * @phpstan-param ReflectionMethod|ReflectionProperty $reflector
     * @phpstan-param class-string<GQL\Field> $fieldAnnotationName
     *
     * @throws AnnotationException
     */
    private static function getTypeFieldConfigurationFromReflector(GraphClass $graphClass, Reflector $reflector, string $fieldAnnotationName, string $currentValue = 'value'): array
    {
        $annotations = $graphClass->getAnnotations($reflector);

        $fieldAnnotation = self::getFirstAnnotationMatching($annotations, $fieldAnnotationName);
        $accessAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Access::class);
        $publicAnnotation = self::getFirstAnnotationMatching($annotations, GQL\IsPublic::class);

        if (null === $fieldAnnotation) {
            if (null !== $accessAnnotation || null !== $publicAnnotation) {
                throw new InvalidArgumentException(sprintf('The annotations "@Access" and/or "@Visible" defined on "%s" are only usable in addition of annotation "@Field"', $reflector->getName()));
            }

            return [];
        }

        if ($reflector instanceof ReflectionMethod && !$reflector->isPublic()) {
            throw new InvalidArgumentException(sprintf('The Annotation "@Field" can only be applied to public method. The method "%s" is not public.', $reflector->getName()));
        }

        $fieldName = $reflector->getName();
        $fieldConfiguration = [];

        if (isset($fieldAnnotation->type)) {
            $fieldConfiguration['type'] = $fieldAnnotation->type;
        }

        $fieldConfiguration = self::getDescriptionConfiguration($annotations, true) + $fieldConfiguration;

        $args = [];

        foreach ($fieldAnnotation->args as $arg) {
            $args[$arg->name] = ['type' => $arg->type];

            if (isset($arg->description)) {
                $args[$arg->name]['description'] = $arg->description;
            }

            if (isset($arg->default)) {
                $args[$arg->name]['defaultValue'] = $arg->default;
            }
        }

        if (empty($fieldAnnotation->args) && $reflector instanceof ReflectionMethod) {
            $args = self::guessArgs($reflector);
        }

        if (!empty($args)) {
            $fieldConfiguration['args'] = $args;
        }

        $fieldName = $fieldAnnotation->name ?? $fieldName;

        if (isset($fieldAnnotation->resolve)) {
            $fieldConfiguration['resolve'] = self::formatExpression($fieldAnnotation->resolve);
        } else {
            if ($reflector instanceof ReflectionMethod) {
                $fieldConfiguration['resolve'] = self::formatExpression(sprintf('call(%s.%s, %s)', $currentValue, $reflector->getName(), self::formatArgsForExpression($args)));
            } else {
                if ($fieldName !== $reflector->getName() || 'value' !== $currentValue) {
                    $fieldConfiguration['resolve'] = self::formatExpression(sprintf('%s.%s', $currentValue, $reflector->getName()));
                }
            }
        }

        if ($fieldAnnotation->argsBuilder) {
            if (is_string($fieldAnnotation->argsBuilder)) {
                $fieldConfiguration['argsBuilder'] = $fieldAnnotation->argsBuilder;
            } elseif (is_array($fieldAnnotation->argsBuilder)) {
                list($builder, $builderConfig) = $fieldAnnotation->argsBuilder;
                $fieldConfiguration['argsBuilder'] = ['builder' => $builder, 'config' => $builderConfig];
            } else {
                throw new InvalidArgumentException(sprintf('The attribute "argsBuilder" on GraphQL annotation "@%s" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $fieldAnnotationName, $reflector->getName()));
            }
        }

        if ($fieldAnnotation->fieldBuilder) {
            if (is_string($fieldAnnotation->fieldBuilder)) {
                $fieldConfiguration['builder'] = $fieldAnnotation->fieldBuilder;
            } elseif (is_array($fieldAnnotation->fieldBuilder)) {
                list($builder, $builderConfig) = $fieldAnnotation->fieldBuilder;
                $fieldConfiguration['builder'] = $builder;
                $fieldConfiguration['builderConfig'] = $builderConfig ?: [];
            } else {
                throw new InvalidArgumentException(sprintf('The attribute "fieldBuilder" on GraphQL annotation "@%s" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $fieldAnnotationName, $reflector->getName()));
            }
        } else {
            if (!isset($fieldAnnotation->type)) {
                if ($reflector instanceof ReflectionMethod) {
                    /** @var ReflectionMethod $reflector */
                    if ($reflector->hasReturnType()) {
                        try {
                            // @phpstan-ignore-next-line
                            $fieldConfiguration['type'] = self::resolveGraphQLTypeFromReflectionType($reflector->getReturnType(), self::VALID_OUTPUT_TYPES);
                        } catch (Exception $e) {
                            throw new InvalidArgumentException(sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed from type hint "%s"', $fieldAnnotationName, $reflector->getName(), (string) $reflector->getReturnType()));
                        }
                    } else {
                        throw new InvalidArgumentException(sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed as there is not return type hint.', $fieldAnnotationName, $reflector->getName()));
                    }
                } else {
                    try {
                        $fieldConfiguration['type'] = self::guessType($graphClass, $annotations);
                    } catch (Exception $e) {
                        throw new InvalidArgumentException(sprintf('The attribute "type" on "@%s" defined on "%s" is required and cannot be auto-guessed : %s.', $fieldAnnotationName, $reflector->getName(), $e->getMessage()));
                    }
                }
            }
        }

        if ($accessAnnotation) {
            $fieldConfiguration['access'] = self::formatExpression($accessAnnotation->value);
        }

        if ($publicAnnotation) {
            $fieldConfiguration['public'] = self::formatExpression($publicAnnotation->value);
        }

        if ($fieldAnnotation->complexity) {
            $fieldConfiguration['complexity'] = self::formatExpression($fieldAnnotation->complexity);
        }

        return [$fieldName => $fieldConfiguration];
    }

    /**
     * Create GraphQL input fields configuration based on annotations.
     *
     * @param ReflectionProperty[] $reflectors
     *
     * @throws AnnotationException
     */
    private static function getGraphQLInputFieldsFromAnnotations(GraphClass $graphClass, array $reflectors): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $annotations = $graphClass->getAnnotations($reflector);

            /** @var GQL\Field $fieldAnnotation */
            $fieldAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Field::class);

            // Ignore field with resolver when the type is an Input
            if (isset($fieldAnnotation->resolve)) {
                return [];
            }

            $fieldName = $reflector->getName();
            $fieldType = $fieldAnnotation->type;
            $fieldConfiguration = [];
            if ($fieldType) {
                // Resolve a PHP class from a GraphQL type
                $resolvedType = self::$classesMap[$fieldType] ?? null;
                // We found a type but it is not allowed
                if (null !== $resolvedType && !in_array($resolvedType['type'], self::VALID_INPUT_TYPES)) {
                    throw new InvalidArgumentException(sprintf('The type "%s" on "%s" is a "%s" not valid on an Input @Field. Only Input, Scalar and Enum are allowed.', $fieldType, $reflector->getName(), $resolvedType['type']));
                }

                $fieldConfiguration['type'] = $fieldType;
            }

            $fieldConfiguration = array_merge(self::getDescriptionConfiguration($annotations, true), $fieldConfiguration);
            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }

    /**
     * Create GraphQL type fields configuration based on annotations.
     *
     * @phpstan-param class-string<GQL\Field> $fieldAnnotationName
     *
     * @param ReflectionProperty[]|ReflectionMethod[] $reflectors
     *
     * @throws AnnotationException
     */
    private static function getGraphQLTypeFieldsFromAnnotations(GraphClass $graphClass, array $reflectors, string $fieldAnnotationName = GQL\Field::class, string $currentValue = 'value'): array
    {
        $fields = [];

        foreach ($reflectors as $reflector) {
            $fields = array_merge($fields, self::getTypeFieldConfigurationFromReflector($graphClass, $reflector, $fieldAnnotationName, $currentValue));
        }

        return $fields;
    }

    /**
     * @phpstan-param class-string<GQL\Query|GQL\Mutation> $expectedAnnotation
     *
     * Return fields config from Provider methods.
     * Loop through configured provider and extract fields targeting the targetType.
     */
    private static function getGraphQLFieldsFromProviders(GraphClass $graphClass, string $expectedAnnotation, string $targetType, bool $isDefaultTarget = false): array
    {
        $fields = [];
        foreach (self::$providers as ['metadata' => $providerMetadata, 'annotation' => $providerAnnotation]) {
            $defaultAccessAnnotation = self::getFirstAnnotationMatching($providerMetadata->getAnnotations(), GQL\Access::class);
            $defaultIsPublicAnnotation = self::getFirstAnnotationMatching($providerMetadata->getAnnotations(), GQL\IsPublic::class);

            $defaultAccess = $defaultAccessAnnotation ? self::formatExpression($defaultAccessAnnotation->value) : false;
            $defaultIsPublic = $defaultIsPublicAnnotation ? self::formatExpression($defaultIsPublicAnnotation->value) : false;

            $methods = [];
            // First found the methods matching the targeted type
            foreach ($providerMetadata->getMethods() as $method) {
                $annotations = $providerMetadata->getAnnotations($method);

                $annotation = self::getFirstAnnotationMatching($annotations, [GQL\Mutation::class, GQL\Query::class]);
                if (null === $annotation) {
                    continue;
                }

                $annotationTargets = $annotation->targetType ?? null;

                if (null === $annotationTargets) {
                    if ($isDefaultTarget) {
                        $annotationTargets = [$targetType];
                        if (!$annotation instanceof $expectedAnnotation) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                if (!in_array($targetType, $annotationTargets)) {
                    continue;
                }

                if (!$annotation instanceof $expectedAnnotation) {
                    if (GQL\Mutation::class == $expectedAnnotation) {
                        $message = sprintf('The provider "%s" try to add a query field on type "%s" (through @Query on method "%s") but "%s" is a mutation.', $providerMetadata->getName(), $targetType, $method->getName(), $targetType);
                    } else {
                        $message = sprintf('The provider "%s" try to add a mutation on type "%s" (through @Mutation on method "%s") but "%s" is not a mutation.', $providerMetadata->getName(), $targetType, $method->getName(), $targetType);
                    }

                    throw new InvalidArgumentException($message);
                }
                $methods[$method->getName()] = $method;
            }

            $currentValue = sprintf("service('%s')", self::formatNamespaceForExpression($providerMetadata->getName()));
            $providerFields = self::getGraphQLTypeFieldsFromAnnotations($graphClass, $methods, $expectedAnnotation, $currentValue);
            foreach ($providerFields as $fieldName => $fieldConfig) {
                if (isset($providerAnnotation->prefix)) {
                    $fieldName = sprintf('%s%s', $providerAnnotation->prefix, $fieldName);
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
     */
    private static function getDescriptionConfiguration(array $annotations, bool $withDeprecation = false): array
    {
        $config = [];
        $descriptionAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Description::class);
        if (null !== $descriptionAnnotation) {
            $config['description'] = $descriptionAnnotation->value;
        }

        if ($withDeprecation) {
            $deprecatedAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Deprecated::class);
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
     * Get the first annotation matching given class.
     *
     * @phpstan-template T of object
     * @phpstan-param class-string<T>|class-string<T>[] $annotationClass
     * @phpstan-return T|null
     *
     * @param string|array $annotationClass
     *
     * @return object|null
     */
    private static function getFirstAnnotationMatching(array $annotations, $annotationClass)
    {
        if (is_string($annotationClass)) {
            $annotationClass = [$annotationClass];
        }

        foreach ($annotations as $annotation) {
            foreach ($annotationClass as $class) {
                if ($annotation instanceof $class) {
                    return $annotation;
                }
            }
        }

        return null;
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
     * Try to guess a field type base on his annotations.
     *
     * @throws RuntimeException
     */
    private static function guessType(GraphClass $graphClass, array $annotations): string
    {
        $columnAnnotation = self::getFirstAnnotationMatching($annotations, Column::class);
        if (null !== $columnAnnotation) {
            $type = self::resolveTypeFromDoctrineType($columnAnnotation->type);
            $nullable = $columnAnnotation->nullable;
            if ($type) {
                return $nullable ? $type : sprintf('%s!', $type);
            } else {
                throw new RuntimeException(sprintf('Unable to auto-guess GraphQL type from Doctrine type "%s"', $columnAnnotation->type));
            }
        }

        $associationAnnotations = [
            OneToMany::class => true,
            OneToOne::class => false,
            ManyToMany::class => true,
            ManyToOne::class => false,
        ];

        $associationAnnotation = self::getFirstAnnotationMatching($annotations, array_keys($associationAnnotations));
        if (null !== $associationAnnotation) {
            $target = self::fullyQualifiedClassName($associationAnnotation->targetEntity, $graphClass->getNamespaceName());
            $type = self::resolveTypeFromClass($target, ['type']);

            if ($type) {
                $isMultiple = $associationAnnotations[get_class($associationAnnotation)];
                if ($isMultiple) {
                    return sprintf('[%s]!', $type);
                } else {
                    $isNullable = false;
                    $joinColumn = self::getFirstAnnotationMatching($annotations, JoinColumn::class);
                    if (null !== $joinColumn) {
                        $isNullable = $joinColumn->nullable;
                    }

                    return sprintf('%s%s', $type, $isNullable ? '' : '!');
                }
            } else {
                throw new RuntimeException(sprintf('Unable to auto-guess GraphQL type from Doctrine target class "%s" (check if the target class is a GraphQL type itself (with a @GQL\Type annotation).', $target));
            }
        }

        throw new InvalidArgumentException(sprintf('No Doctrine ORM annotation found.'));
    }

    /**
     * Resolve a FQN from classname and namespace.
     *
     * @internal
     */
    public static function fullyQualifiedClassName(string $className, string $namespace): string
    {
        if (false === strpos($className, '\\') && $namespace) {
            return $namespace.'\\'.$className;
        }

        return $className;
    }

    /**
     * Resolve a GraphQLType from a doctrine type.
     */
    private static function resolveTypeFromDoctrineType(string $doctrineType): ?string
    {
        if (isset(self::$doctrineMapping[$doctrineType])) {
            return self::$doctrineMapping[$doctrineType];
        }

        switch ($doctrineType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return 'Int';
            case 'string':
            case 'text':
                return 'String';
            case 'bool':
            case 'boolean':
                return 'Boolean';
            case 'float':
            case 'decimal':
                return 'Float';
            default:
                return null;
        }
    }

    /**
     * Transform a method arguments from reflection to a list of GraphQL argument.
     */
    private static function guessArgs(ReflectionMethod $method): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $index => $parameter) {
            if (!$parameter->hasType()) {
                throw new InvalidArgumentException(sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed as there is not type hint.', $index + 1, $parameter->getName(), $method->getName()));
            }

            try {
                // @phpstan-ignore-next-line
                $gqlType = self::resolveGraphQLTypeFromReflectionType($parameter->getType(), self::VALID_INPUT_TYPES, $parameter->isDefaultValueAvailable());
            } catch (Exception $e) {
                throw new InvalidArgumentException(sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed : %s".', $index + 1, $parameter->getName(), $method->getName(), $e->getMessage()));
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

    private static function resolveGraphQLTypeFromReflectionType(ReflectionNamedType $type, array $filterGraphQLTypes = [], bool $isOptional = false): string
    {
        $sType = $type->getName();
        if ($type->isBuiltin()) {
            $gqlType = self::resolveTypeFromPhpType($sType);
            if (null === $gqlType) {
                throw new RuntimeException(sprintf('No corresponding GraphQL type found for builtin type "%s"', $sType));
            }
        } else {
            $gqlType = self::resolveTypeFromClass($sType, $filterGraphQLTypes);
            if (null === $gqlType) {
                throw new RuntimeException(sprintf('No corresponding GraphQL %s found for class "%s"', $filterGraphQLTypes ? implode(',', $filterGraphQLTypes) : 'object', $sType));
            }
        }

        return sprintf('%s%s', $gqlType, ($type->allowsNull() || $isOptional) ? '' : '!');
    }

    /**
     * Resolve a GraphQL Type from a class name.
     */
    private static function resolveTypeFromClass(string $className, array $wantedTypes = []): ?string
    {
        foreach (self::$classesMap as $gqlType => $config) {
            if ($config['class'] === $className) {
                if (in_array($config['type'], $wantedTypes)) {
                    return $gqlType;
                }
            }
        }

        return null;
    }

    /**
     * Search the classes map for class by predicate.
     *
     * @return array
     */
    private static function searchClassesMapBy(callable $predicate, string $type)
    {
        $classNames = [];
        foreach (self::$classesMap as $gqlType => $config) {
            if ($config['type'] !== $type) {
                continue;
            }

            if ($predicate($gqlType, $config)) {
                $classNames[$gqlType] = $config;
            }
        }

        return $classNames;
    }

    /**
     * Convert a PHP Builtin type to a GraphQL type.
     */
    private static function resolveTypeFromPhpType(string $phpType): ?string
    {
        switch ($phpType) {
            case 'boolean':
            case 'bool':
                return 'Boolean';
            case 'integer':
            case 'int':
                return 'Int';
            case 'float':
            case 'double':
                return 'Float';
            case 'string':
                return 'String';
            default:
                return null;
        }
    }
}
