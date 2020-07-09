<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\EdgeInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use function array_filter;
use function array_keys;
use function array_map;
use function array_unshift;
use function class_exists;
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
    private static ?AnnotationReader $annotationReader = null;
    private static array $classesMap = [];
    private static array $providers = [];
    private static array $doctrineMapping = [];
    private static array $classAnnotationsCache = [];

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
     */
    public static function preParse(SplFileInfo $file, ContainerBuilder $container, array $configs = []): void
    {
        $container->setParameter('overblog_graphql_types.classes_map', self::processFile($file, $container, $configs, true));
    }

    /**
     * @throws InvalidArgumentException
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
        self::$classAnnotationsCache = [];
        self::$annotationReader = null;
    }

    /**
     * Process a file.
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
            [$reflectionEntity, $classAnnotations, $properties, $methods] = self::extractClassAnnotations($className);
            $gqlTypes = [];

            foreach ($classAnnotations as $classAnnotation) {
                $gqlTypes = self::classAnnotationsToGQLConfiguration(
                    $reflectionEntity,
                    $classAnnotation,
                    $configs,
                    $classAnnotations,
                    $properties,
                    $methods,
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
        ReflectionClass $reflectionEntity,
        object $classAnnotation,
        array $configs,
        array $classAnnotations,
        array $properties,
        array $methods,
        array $gqlTypes,
        bool $preProcess
    ): array {
        $gqlConfiguration = $gqlType = $gqlName = null;

        switch (true) {
            case $classAnnotation instanceof GQL\Type:
                $gqlType = self::GQL_TYPE;
                $gqlName = $classAnnotation->name ?: $reflectionEntity->getShortName();
                if (!$preProcess) {
                    $gqlConfiguration = self::typeAnnotationToGQLConfiguration(
                        $reflectionEntity, $classAnnotation, $gqlName, $classAnnotations, $properties, $methods, $configs
                    );

                    if ($classAnnotation instanceof GQL\Relay\Connection) {
                        if (!$reflectionEntity->implementsInterface(ConnectionInterface::class)) {
                            throw new InvalidArgumentException(sprintf('The annotation @Connection on class "%s" can only be used on class implementing the ConnectionInterface.', $reflectionEntity->getName()));
                        }

                        if (!($classAnnotation->edge xor $classAnnotation->node)) {
                            throw new InvalidArgumentException(sprintf('The annotation @Connection on class "%s" is invalid. You must define the "edge" OR the "node" attribute.', $reflectionEntity->getName()));
                        }

                        $edgeType = $classAnnotation->edge;
                        if (!$edgeType) {
                            $edgeType = sprintf('%sEdge', $gqlName);
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
                $gqlName = $classAnnotation->name ?: self::suffixName($reflectionEntity->getShortName(), 'Input');
                if (!$preProcess) {
                    $gqlConfiguration = self::inputAnnotationToGQLConfiguration(
                        $classAnnotation, $classAnnotations, $properties, $reflectionEntity->getNamespaceName()
                    );
                }
                break;

            case $classAnnotation instanceof GQL\Scalar:
                $gqlType = self::GQL_SCALAR;
                if (!$preProcess) {
                    $gqlConfiguration = self::scalarAnnotationToGQLConfiguration(
                        $reflectionEntity->getName(), $classAnnotation, $classAnnotations
                    );
                }
                break;

            case $classAnnotation instanceof GQL\Enum:
                $gqlType = self::GQL_ENUM;
                if (!$preProcess) {
                    $gqlConfiguration = self::enumAnnotationToGQLConfiguration(
                        $classAnnotation, $classAnnotations, $reflectionEntity->getConstants()
                    );
                }
                break;

            case $classAnnotation instanceof GQL\Union:
                $gqlType = self::GQL_UNION;
                if (!$preProcess) {
                    $gqlConfiguration = self::unionAnnotationToGQLConfiguration(
                        $reflectionEntity->getName(), $classAnnotation, $classAnnotations, $methods
                    );
                }
                break;

            case $classAnnotation instanceof GQL\TypeInterface:
                $gqlType = self::GQL_INTERFACE;
                if (!$preProcess) {
                    $gqlConfiguration = self::typeInterfaceAnnotationToGQLConfiguration(
                        $classAnnotation, $classAnnotations, $properties, $methods, $reflectionEntity->getNamespaceName()
                    );
                }
                break;

            case $classAnnotation instanceof GQL\Provider:
                if ($preProcess) {
                    self::$providers[$reflectionEntity->getName()] = ['annotation' => $classAnnotation, 'methods' => $methods, 'annotations' => $classAnnotations];
                }
                break;
        }

        if (null !== $gqlType) {
            if (!$gqlName) {
                $gqlName = $classAnnotation->name ?: $reflectionEntity->getShortName();
            }

            if ($preProcess) {
                if (isset(self::$classesMap[$gqlName])) {
                    throw new InvalidArgumentException(sprintf('The GraphQL type "%s" has already been registered in class "%s"', $gqlName, self::$classesMap[$gqlName]['class']));
                }
                self::$classesMap[$gqlName] = ['type' => $gqlType, 'class' => $reflectionEntity->getName()];
            } else {
                $gqlTypes = [$gqlName => $gqlConfiguration] + $gqlTypes;
            }
        }

        return $gqlTypes;
    }

    /**
     * @throws ReflectionException
     */
    private static function extractClassAnnotations(string $className): array
    {
        if (!isset(self::$classAnnotationsCache[$className])) {
            $annotationReader = self::getAnnotationReader();
            $reflectionEntity = new ReflectionClass($className); // @phpstan-ignore-line
            $classAnnotations = $annotationReader->getClassAnnotations($reflectionEntity);

            $properties = [];
            $reflectionClass = new ReflectionClass($className); // @phpstan-ignore-line
            do {
                foreach ($reflectionClass->getProperties() as $property) {
                    if (isset($properties[$property->getName()])) {
                        continue;
                    }
                    $properties[$property->getName()] = ['property' => $property, 'annotations' => $annotationReader->getPropertyAnnotations($property)];
                }
            } while ($reflectionClass = $reflectionClass->getParentClass());

            $methods = [];
            foreach ($reflectionEntity->getMethods() as $method) {
                $methods[$method->getName()] = ['method' => $method, 'annotations' => $annotationReader->getMethodAnnotations($method)];
            }

            self::$classAnnotationsCache[$className] = [$reflectionEntity, $classAnnotations, $properties, $methods];
        }

        return self::$classAnnotationsCache[$className];
    }

    private static function typeAnnotationToGQLConfiguration(
        ReflectionClass $reflectionEntity,
        GQL\Type $classAnnotation,
        string $gqlName,
        array $classAnnotations,
        array $properties,
        array $methods,
        array $configs
    ): array {
        $rootQueryType = $configs['definitions']['schema']['default']['query'] ?? null;
        $rootMutationType = $configs['definitions']['schema']['default']['mutation'] ?? null;
        $isRootQuery = ($rootQueryType && $gqlName === $rootQueryType);
        $isRootMutation = ($rootMutationType && $gqlName === $rootMutationType);
        $currentValue = ($isRootQuery || $isRootMutation) ? sprintf("service('%s')", self::formatNamespaceForExpression($reflectionEntity->getName())) : 'value';

        $gqlConfiguration = self::graphQLTypeConfigFromAnnotation($classAnnotation, $classAnnotations, $properties, $methods, $reflectionEntity->getNamespaceName(), $currentValue);
        $providerFields = self::getGraphQLFieldsFromProviders($reflectionEntity->getNamespaceName(), $isRootMutation ? 'Mutation' : 'Query', $gqlName, ($isRootQuery || $isRootMutation));
        $gqlConfiguration['config']['fields'] = $providerFields + $gqlConfiguration['config']['fields'];

        if ($classAnnotation instanceof GQL\Relay\Edge) {
            if (!$reflectionEntity->implementsInterface(EdgeInterface::class)) {
                throw new InvalidArgumentException(sprintf('The annotation @Edge on class "%s" can only be used on class implementing the EdgeInterface.', $reflectionEntity->getName()));
            }
            if (!isset($gqlConfiguration['config']['builders'])) {
                $gqlConfiguration['config']['builders'] = [];
            }
            array_unshift($gqlConfiguration['config']['builders'], ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => $classAnnotation->node]]);
        }

        return $gqlConfiguration;
    }

    private static function getAnnotationReader(): AnnotationReader
    {
        if (null === self::$annotationReader) {
            if (!class_exists('\\Doctrine\\Common\\Annotations\\AnnotationReader') ||
                !class_exists('\\Doctrine\\Common\\Annotations\\AnnotationRegistry')) {
                throw new RuntimeException('In order to use graphql annotation, you need to require doctrine annotations');
            }

            AnnotationRegistry::registerLoader('class_exists');
            self::$annotationReader = new AnnotationReader();
        }

        return self::$annotationReader;
    }

    private static function graphQLTypeConfigFromAnnotation(GQL\Type $typeAnnotation, array $classAnnotations, array $properties, array $methods, string $namespace, string $currentValue): array
    {
        $typeConfiguration = [];

        $fields = self::getGraphQLFieldsFromAnnotations($namespace, $properties, false, false, $currentValue);
        $fields = self::getGraphQLFieldsFromAnnotations($namespace, $methods, false, true, $currentValue) + $fields;

        $typeConfiguration['fields'] = $fields;
        $typeConfiguration = self::getDescriptionConfiguration($classAnnotations) + $typeConfiguration;

        if ($typeAnnotation->interfaces) {
            $typeConfiguration['interfaces'] = $typeAnnotation->interfaces;
        }

        if ($typeAnnotation->resolveField) {
            $typeConfiguration['resolveField'] = self::formatExpression($typeAnnotation->resolveField);
        }

        if ($typeAnnotation->builders && !empty($typeAnnotation->builders)) {
            $typeConfiguration['builders'] = array_map(function ($fieldsBuilderAnnotation) {
                return ['builder' => $fieldsBuilderAnnotation->builder, 'builderConfig' => $fieldsBuilderAnnotation->builderConfig];
            }, $typeAnnotation->builders);
        }

        $publicAnnotation = self::getFirstAnnotationMatching($classAnnotations, GQL\IsPublic::class);
        if ($publicAnnotation) {
            $typeConfiguration['fieldsDefaultPublic'] = self::formatExpression($publicAnnotation->value);
        }

        $accessAnnotation = self::getFirstAnnotationMatching($classAnnotations, GQL\Access::class);
        if ($accessAnnotation) {
            $typeConfiguration['fieldsDefaultAccess'] = self::formatExpression($accessAnnotation->value);
        }

        return ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration];
    }

    /**
     * Create a GraphQL Interface type configuration from annotations on properties.
     */
    private static function typeInterfaceAnnotationToGQLConfiguration(GQL\TypeInterface $interfaceAnnotation, array $classAnnotations, array $properties, array $methods, string $namespace): array
    {
        $interfaceConfiguration = [];

        $fields = self::getGraphQLFieldsFromAnnotations($namespace, $properties);
        $fields = self::getGraphQLFieldsFromAnnotations($namespace, $methods, false, true) + $fields;

        $interfaceConfiguration['fields'] = $fields;
        $interfaceConfiguration = self::getDescriptionConfiguration($classAnnotations) + $interfaceConfiguration;

        $interfaceConfiguration['resolveType'] = self::formatExpression($interfaceAnnotation->resolveType);

        return ['type' => 'interface', 'config' => $interfaceConfiguration];
    }

    /**
     * Create a GraphQL Input type configuration from annotations on properties.
     */
    private static function inputAnnotationToGQLConfiguration(GQL\Input $inputAnnotation, array $classAnnotations, array $properties, string $namespace): array
    {
        $inputConfiguration = [];
        $fields = self::getGraphQLFieldsFromAnnotations($namespace, $properties, true);

        $inputConfiguration['fields'] = $fields;
        $inputConfiguration = self::getDescriptionConfiguration($classAnnotations) + $inputConfiguration;

        return ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration];
    }

    /**
     * Get a GraphQL scalar configuration from given scalar annotation.
     */
    private static function scalarAnnotationToGQLConfiguration(string $className, GQL\Scalar $scalarAnnotation, array $classAnnotations): array
    {
        $scalarConfiguration = [];

        if ($scalarAnnotation->scalarType) {
            $scalarConfiguration['scalarType'] = self::formatExpression($scalarAnnotation->scalarType);
        } else {
            $scalarConfiguration = [
                'serialize' => [$className, 'serialize'],
                'parseValue' => [$className, 'parseValue'],
                'parseLiteral' => [$className, 'parseLiteral'],
            ];
        }

        $scalarConfiguration = self::getDescriptionConfiguration($classAnnotations) + $scalarConfiguration;

        return ['type' => 'custom-scalar', 'config' => $scalarConfiguration];
    }

    /**
     * Get a GraphQL Enum configuration from given enum annotation.
     */
    private static function enumAnnotationToGQLConfiguration(GQL\Enum $enumAnnotation, array $classAnnotations, array $constants): array
    {
        $enumValues = $enumAnnotation->values ? $enumAnnotation->values : [];

        $values = [];

        foreach ($constants as $name => $value) {
            $valueAnnotation = current(array_filter($enumValues, function ($enumValueAnnotation) use ($name) {
                return $enumValueAnnotation->name == $name;
            }));
            $valueConfig = [];
            $valueConfig['value'] = $value;

            if ($valueAnnotation && $valueAnnotation->description) {
                $valueConfig['description'] = $valueAnnotation->description;
            }

            if ($valueAnnotation && $valueAnnotation->deprecationReason) {
                $valueConfig['deprecationReason'] = $valueAnnotation->deprecationReason;
            }

            $values[$name] = $valueConfig;
        }

        $enumConfiguration = ['values' => $values];
        $enumConfiguration = self::getDescriptionConfiguration($classAnnotations) + $enumConfiguration;

        return ['type' => 'enum', 'config' => $enumConfiguration];
    }

    /**
     * Get a GraphQL Union configuration from given union annotation.
     */
    private static function unionAnnotationToGQLConfiguration(string $className, GQL\Union $unionAnnotation, array $classAnnotations, array $methods): array
    {
        $unionConfiguration = ['types' => $unionAnnotation->types];
        $unionConfiguration = self::getDescriptionConfiguration($classAnnotations) + $unionConfiguration;

        if ($unionAnnotation->resolveType) {
            $unionConfiguration['resolveType'] = self::formatExpression($unionAnnotation->resolveType);
        } else {
            if (isset($methods['resolveType'])) {
                $method = $methods['resolveType']['method'];
                if ($method->isStatic() && $method->isPublic()) {
                    $unionConfiguration['resolveType'] = self::formatExpression(sprintf("@=call('%s::%s', [service('overblog_graphql.type_resolver'), value], true)", self::formatNamespaceForExpression($className), 'resolveType'));
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
     * Create GraphQL fields configuration based on annotations.
     */
    private static function getGraphQLFieldsFromAnnotations(string $namespace, array $propertiesOrMethods, bool $isInput = false, bool $isMethod = false, string $currentValue = 'value', string $fieldAnnotationName = 'Field'): array
    {
        $fields = [];
        foreach ($propertiesOrMethods as $target => $config) {
            $annotations = $config['annotations'];
            $method = $isMethod ? $config['method'] : false;

            $fieldAnnotation = self::getFirstAnnotationMatching($annotations, sprintf('Overblog\GraphQLBundle\Annotation\%s', $fieldAnnotationName));
            $accessAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Access::class);
            $publicAnnotation = self::getFirstAnnotationMatching($annotations, GQL\IsPublic::class);

            if (!$fieldAnnotation) {
                if ($accessAnnotation || $publicAnnotation) {
                    throw new InvalidArgumentException(sprintf('The annotations "@Access" and/or "@Visible" defined on "%s" are only usable in addition of annotation "@Field"', $target));
                }
                continue;
            }

            if ($isMethod && !$method->isPublic()) {
                throw new InvalidArgumentException(sprintf('The Annotation "@Field" can only be applied to public method. The method "%s" is not public.', $target));
            }

            // Ignore field with resolver when the type is an Input
            if ($fieldAnnotation->resolve && $isInput) {
                continue;
            }

            $fieldName = $target;
            $fieldType = $fieldAnnotation->type;
            $fieldConfiguration = [];
            if ($fieldType) {
                $resolvedType = self::resolveClassFromType($fieldType);
                if (null !== $resolvedType && $isInput && !in_array($resolvedType['type'], self::VALID_INPUT_TYPES)) {
                    throw new InvalidArgumentException(sprintf('The type "%s" on "%s" is a "%s" not valid on an Input @Field. Only Input, Scalar and Enum are allowed.', $fieldType, $target, $resolvedType['type']));
                }

                $fieldConfiguration['type'] = $fieldType;
            }

            $fieldConfiguration = self::getDescriptionConfiguration($annotations, true) + $fieldConfiguration;

            if (!$isInput) {
                $args = self::getArgs($fieldAnnotation->args, $isMethod && !$fieldAnnotation->argsBuilder ? $method : null);

                if (!empty($args)) {
                    $fieldConfiguration['args'] = $args;
                }

                $fieldName = $fieldAnnotation->name ?: $fieldName;

                if ($fieldAnnotation->resolve) {
                    $fieldConfiguration['resolve'] = self::formatExpression($fieldAnnotation->resolve);
                } else {
                    if ($isMethod) {
                        $fieldConfiguration['resolve'] = self::formatExpression(sprintf('call(%s.%s, %s)', $currentValue, $target, self::formatArgsForExpression($args)));
                    } else {
                        if ($fieldName !== $target || 'value' !== $currentValue) {
                            $fieldConfiguration['resolve'] = self::formatExpression(sprintf('%s.%s', $currentValue, $target));
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
                        throw new InvalidArgumentException(sprintf('The attribute "argsBuilder" on GraphQL annotation "@%s" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $fieldAnnotationName, $target));
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
                        throw new InvalidArgumentException(sprintf('The attribute "argsBuilder" on GraphQL annotation "@%s" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $fieldAnnotationName, $target));
                    }
                } else {
                    if (!$fieldType) {
                        if ($isMethod) {
                            if ($method->hasReturnType()) {
                                try {
                                    $fieldConfiguration['type'] = self::resolveGraphQLTypeFromReflectionType($method->getReturnType(), self::VALID_OUTPUT_TYPES);
                                } catch (Exception $e) {
                                    throw new InvalidArgumentException(sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed from type hint "%s"', $fieldAnnotationName, $target, (string) $method->getReturnType()));
                                }
                            } else {
                                throw new InvalidArgumentException(sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed as there is not return type hint.', $fieldAnnotationName, $target));
                            }
                        } else {
                            try {
                                $fieldConfiguration['type'] = self::guessType($namespace, $annotations);
                            } catch (Exception $e) {
                                throw new InvalidArgumentException(sprintf('The attribute "type" on "@%s" defined on "%s" is required and cannot be auto-guessed : %s.', $fieldAnnotationName, $target, $e->getMessage()));
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
            }

            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }

    /**
     * Return fields config from Provider methods.
     */
    private static function getGraphQLFieldsFromProviders(string $namespace, string $annotationName, string $targetType, bool $isRoot = false): array
    {
        $fields = [];
        foreach (self::$providers as $className => $configuration) {
            $providerMethods = $configuration['methods'];
            $providerAnnotation = $configuration['annotation'];
            $providerAnnotations = $configuration['annotations'];

            $defaultAccessAnnotation = self::getFirstAnnotationMatching($providerAnnotations, GQL\Access::class);
            $defaultIsPublicAnnotation = self::getFirstAnnotationMatching($providerAnnotations, GQL\IsPublic::class);

            $defaultAccess = $defaultAccessAnnotation ? self::formatExpression($defaultAccessAnnotation->value) : false;
            $defaultIsPublic = $defaultIsPublicAnnotation ? self::formatExpression($defaultIsPublicAnnotation->value) : false;

            $filteredMethods = [];
            foreach ($providerMethods as $methodName => $config) {
                $annotations = $config['annotations'];

                $annotation = self::getFirstAnnotationMatching($annotations, sprintf('Overblog\\GraphQLBundle\\Annotation\\%s', $annotationName));
                if (!$annotation) {
                    continue;
                }

                $annotationTarget = 'Query' === $annotationName ? $annotation->targetType : null;
                if (!$annotationTarget && $isRoot) {
                    $annotationTarget = $targetType;
                }

                if ($annotationTarget !== $targetType) {
                    continue;
                }

                $filteredMethods[$methodName] = $config;
            }

            $currentValue = sprintf("service('%s')", self::formatNamespaceForExpression($className));
            $providerFields = self::getGraphQLFieldsFromAnnotations($namespace, $filteredMethods, false, true, $currentValue, $annotationName);
            foreach ($providerFields as $fieldName => $fieldConfig) {
                if ($providerAnnotation->prefix) {
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
        if ($descriptionAnnotation) {
            $config['description'] = $descriptionAnnotation->value;
        }

        if ($withDeprecation) {
            $deprecatedAnnotation = self::getFirstAnnotationMatching($annotations, GQL\Deprecated::class);
            if ($deprecatedAnnotation) {
                $config['deprecationReason'] = $deprecatedAnnotation->value;
            }
        }

        return $config;
    }

    /**
     * Get args config from an array of @Arg annotation or by auto-guessing if a method is provided.
     */
    private static function getArgs(?array $args, ReflectionMethod $method = null): array
    {
        $config = [];
        if (!empty($args)) {
            foreach ($args as $arg) {
                $config[$arg->name] = ['type' => $arg->type]
                    + ($arg->description ? ['description' => $arg->description] : [])
                    + ($arg->default ? ['defaultValue' => $arg->default] : []);
            }
        } elseif ($method) {
            $config = self::guessArgs($method);
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
     * @param string|array $annotationClass
     *
     * @return mixed
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

        return false;
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
     * Try to guess a field type base on is annotations.
     *
     * @throws RuntimeException
     */
    private static function guessType(string $namespace, array $annotations): string
    {
        $columnAnnotation = self::getFirstAnnotationMatching($annotations, Column::class);
        if ($columnAnnotation) {
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
        if ($associationAnnotation) {
            $target = self::fullyQualifiedClassName($associationAnnotation->targetEntity, $namespace);
            $type = self::resolveTypeFromClass($target, ['type']);

            if ($type) {
                $isMultiple = $associationAnnotations[get_class($associationAnnotation)];
                if ($isMultiple) {
                    return sprintf('[%s]!', $type);
                } else {
                    $isNullable = false;
                    $joinColumn = self::getFirstAnnotationMatching($annotations, JoinColumn::class);
                    if ($joinColumn) {
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
     * Resolve a PHP class from a GraphQL type.
     *
     * @return string|array|null
     */
    private static function resolveClassFromType(string $type)
    {
        return self::$classesMap[$type] ?? null;
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
