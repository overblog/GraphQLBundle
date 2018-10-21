<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Overblog\GraphQLBundle\Annotation as GQL;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class AnnotationParser implements PreParserInterface
{
    public const CLASSESMAP_CONTAINER_PARAMETER = 'overblog_graphql_types.classes_map';

    private static $annotationReader = null;
    private static $classesMap = [];
    private static $providers = [];
    private static $doctrineMapping = [];

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function preParse(\SplFileInfo $file, ContainerBuilder $container, array $configs = []): void
    {
        self::proccessFile($file, $container, $configs, true);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function parse(\SplFileInfo $file, ContainerBuilder $container, array $configs = []): array
    {
        return self::proccessFile($file, $container, $configs);
    }

    /**
     * Clear the Annotation parser.
     */
    public static function clear(): void
    {
        self::$classesMap = [];
        self::$providers = [];
        self::$annotationReader = null;
    }

    /**
     * Process a file.
     *
     * @param \SplFileInfo     $file
     * @param ContainerBuilder $container
     * @param bool             $resolveClassMap
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function proccessFile(\SplFileInfo $file, ContainerBuilder $container, array $configs, bool $resolveClassMap = false): array
    {
        self::$doctrineMapping = $configs['doctrine']['types_mapping'];

        $rootQueryType = $configs['definitions']['schema']['default']['query'] ?? false;
        $rootMutationType = $configs['definitions']['schema']['default']['mutation'] ?? false;

        $container->addResource(new FileResource($file->getRealPath()));

        if (!$resolveClassMap) {
            $container->setParameter(self::CLASSESMAP_CONTAINER_PARAMETER, self::$classesMap);
        }

        try {
            $fileContent = \file_get_contents($file->getRealPath());

            $shortClassName = \substr($file->getFilename(), 0, -4);
            if (\preg_match('#namespace (.+);#', $fileContent, $namespace)) {
                $className = $namespace[1].'\\'.$shortClassName;
                $namespace = $namespace[1];
            } else {
                $className = $shortClassName;
            }

            $reflexionEntity = new \ReflectionClass($className);

            $classAnnotations = self::getAnnotationReader()->getClassAnnotations($reflexionEntity);

            $properties = [];
            foreach ($reflexionEntity->getProperties() as $property) {
                $properties[$property->getName()] = ['property' => $property, 'annotations' => self::getAnnotationReader()->getPropertyAnnotations($property)];
            }

            $methods = [];
            foreach ($reflexionEntity->getMethods() as $method) {
                $methods[$method->getName()] = ['method' => $method, 'annotations' => self::getAnnotationReader()->getMethodAnnotations($method)];
            }

            $gqlTypes = [];

            foreach ($classAnnotations as $classAnnotation) {
                $gqlConfiguration = $gqlType = $gqlName = false;

                switch (true) {
                    case $classAnnotation instanceof GQL\Type:
                        $gqlType = 'type';
                        $gqlName = $classAnnotation->name ?: $shortClassName;
                        if (!$resolveClassMap) {
                            $isRootQuery = ($rootQueryType && $gqlName === $rootQueryType);
                            $isRootMutation = ($rootMutationType && $gqlName === $rootMutationType);
                            $currentValue = ($isRootQuery || $isRootMutation) ? \sprintf("service('%s')", self::formatNamespaceForExpression($className)) : 'value';

                            $gqlConfiguration = self::getGraphqlType($classAnnotation, $classAnnotations, $properties, $methods, $namespace, $currentValue);

                            if ($isRootQuery || $isRootMutation) {
                                foreach (self::$providers as $className => $providerMethods) {
                                    $gqlConfiguration['config']['fields'] += self::getGraphqlFieldsFromProvider($className, $providerMethods, $isRootMutation);
                                }
                            }
                        }
                        break;
                    case $classAnnotation instanceof GQL\Input:
                        $gqlType = 'input';
                        $gqlName = $classAnnotation->name ?: self::suffixName($shortClassName, 'Input');
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlInput($classAnnotation, $classAnnotations, $properties, $namespace);
                        }
                        break;
                    case $classAnnotation instanceof GQL\Scalar:
                        $gqlType = 'scalar';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlScalar($className, $classAnnotation, $classAnnotations);
                        }
                        break;
                    case $classAnnotation instanceof GQL\Enum:
                        $gqlType = 'enum';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlEnum($classAnnotation, $classAnnotations, $reflexionEntity->getConstants());
                        }
                        break;
                    case $classAnnotation instanceof GQL\Union:
                        $gqlType = 'union';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlUnion($classAnnotation, $classAnnotations);
                        }
                        break;
                    case $classAnnotation instanceof GQL\TypeInterface:
                        $gqlType = 'interface';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlInterface($classAnnotation, $classAnnotations, $properties, $methods, $namespace);
                        }
                        break;
                    case $classAnnotation instanceof GQL\Provider:
                        if ($resolveClassMap) {
                            self::$providers[$className] = $methods;
                        }
                        break;
                    default:
                        continue;
                }

                if ($gqlType) {
                    if (!$gqlName) {
                        $gqlName = $classAnnotation->name ?: $shortClassName;
                    }

                    if ($resolveClassMap) {
                        if (isset(self::$classesMap[$gqlName])) {
                            throw new InvalidArgumentException(\sprintf('The GraphQL type "%s" has already been registered in class "%s"', $gqlName, self::$classesMap[$gqlName]['class']));
                        }
                        self::$classesMap[$gqlName] = ['type' => $gqlType, 'class' => $className];
                    } else {
                        $gqlTypes += [$gqlName => $gqlConfiguration];
                    }
                }
            }

            return $resolveClassMap ? self::$classesMap : $gqlTypes;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(\sprintf('Failed to parse GraphQL annotations from file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * Retrieve annotation reader.
     *
     * @return AnnotationReader
     */
    private static function getAnnotationReader()
    {
        if (null === self::$annotationReader) {
            if (!\class_exists('\\Doctrine\\Common\\Annotations\\AnnotationReader') ||
                !\class_exists('\\Doctrine\\Common\\Annotations\\AnnotationRegistry')) {
                throw new \Exception('In order to use graphql annotation, you need to require doctrine annotations');
            }

            AnnotationRegistry::registerLoader('class_exists');
            self::$annotationReader = new AnnotationReader();
        }

        return self::$annotationReader;
    }

    /**
     * Create a GraphQL Type configuration from annotations on class, properties and methods.
     *
     * @param GQL\Type $typeAnnotation
     * @param array    $classAnnotations
     * @param array    $properties
     * @param array    $methods
     * @param string   $namespace
     * @param string   $currentValue
     *
     * @return array
     */
    private static function getGraphqlType(GQL\Type $typeAnnotation, array $classAnnotations, array $properties, array $methods, string $namespace, string $currentValue)
    {
        $typeConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $properties, false, false, $currentValue);
        $fields += self::getGraphqlFieldsFromAnnotations($namespace, $methods, false, true, $currentValue);

        $typeConfiguration['fields'] = $fields;
        $typeConfiguration += self::getDescriptionConfiguration($classAnnotations);

        if ($typeAnnotation->interfaces) {
            $typeConfiguration['interfaces'] = $typeAnnotation->interfaces;
        }

        if ($typeAnnotation->resolveField) {
            $typeConfiguration['resolveField'] = self::formatExpression($typeAnnotation->resolveField);
        }

        $publicAnnotation = self::getFirstAnnotationMatching($classAnnotations, 'Overblog\GraphQLBundle\Annotation\IsPublic');
        if ($publicAnnotation) {
            $typeConfiguration['fieldsDefaultPublic'] = self::formatExpression($publicAnnotation->value);
        }

        $accessAnnotation = self::getFirstAnnotationMatching($classAnnotations, 'Overblog\GraphQLBundle\Annotation\Access');
        if ($accessAnnotation) {
            $typeConfiguration['fieldsDefaultAccess'] = self::formatExpression($accessAnnotation->value);
        }

        return ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration];
    }

    /**
     * Create a GraphQL Interface type configuration from annotations on properties.
     *
     * @param string        $shortClassName
     * @param GQL\Interface $interfaceAnnotation
     * @param array         $properties
     * @param array         $methods
     * @param string        $namespace
     *
     * @return array
     */
    private static function getGraphqlInterface(GQL\TypeInterface $interfaceAnnotation, array $classAnnotations, array $properties, array $methods, string $namespace)
    {
        $interfaceConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $properties);
        $fields += self::getGraphqlFieldsFromAnnotations($namespace, $methods, false, true);

        $interfaceConfiguration['fields'] = $fields;
        $interfaceConfiguration += self::getDescriptionConfiguration($classAnnotations);

        $interfaceConfiguration['resolveType'] = $interfaceAnnotation->resolveType;

        return ['type' => 'interface', 'config' => $interfaceConfiguration];
    }

    /**
     * Create a GraphQL Input type configuration from annotations on properties.
     *
     * @param string    $shortClassName
     * @param GQL\Input $inputAnnotation
     * @param array     $properties
     * @param string    $namespace
     *
     * @return array
     */
    private static function getGraphqlInput(GQL\Input $inputAnnotation, array $classAnnotations, array $properties, string $namespace)
    {
        $inputConfiguration = [];
        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $properties, true);

        $inputConfiguration['fields'] = $fields;
        $inputConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration];
    }

    /**
     * Get a Graphql scalar configuration from given scalar annotation.
     *
     * @param string     $shortClassName
     * @param string     $className
     * @param GQL\Scalar $scalarAnnotation
     * @param array      $classAnnotations
     *
     * @return array
     */
    private static function getGraphqlScalar(string $className, GQL\Scalar $scalarAnnotation, array $classAnnotations)
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

        $scalarConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => 'custom-scalar', 'config' => $scalarConfiguration];
    }

    /**
     * Get a Graphql Enum configuration from given enum annotation.
     *
     * @param string   $shortClassName
     * @param GQL\Enum $enumAnnotation
     * @param array    $classAnnotations
     * @param array    $constants
     *
     * @return array
     */
    private static function getGraphqlEnum(GQL\Enum $enumAnnotation, array $classAnnotations, array $constants)
    {
        $enumValues = $enumAnnotation->values ? $enumAnnotation->values : [];

        $values = [];

        foreach ($constants as $name => $value) {
            $valueAnnotation = \current(\array_filter($enumValues, function ($enumValueAnnotation) use ($name) {
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
        $enumConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => 'enum', 'config' => $enumConfiguration];
    }

    /**
     * Get a Graphql Union configuration from given union annotation.
     *
     * @param string    $shortClassName
     * @param GQL\Union $unionAnnotation
     * @param array     $classAnnotations
     *
     * @return array
     */
    private static function getGraphqlUnion(GQL\Union $unionAnnotation, array $classAnnotations): array
    {
        $unionConfiguration = ['types' => $unionAnnotation->types];
        $unionConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => 'union', 'config' => $unionConfiguration];
    }

    /**
     * Create Graphql fields configuration based on annotation.
     *
     * @param string $namespace
     * @param array  $propertiesOrMethods
     * @param bool   $isInput
     * @param bool   $isMethod
     * @param string $currentValue
     *
     * @return array
     */
    private static function getGraphqlFieldsFromAnnotations(string $namespace, array $propertiesOrMethods, bool $isInput = false, bool $isMethod = false, string $currentValue = 'value'): array
    {
        $fields = [];
        foreach ($propertiesOrMethods as $target => $config) {
            $annotations = $config['annotations'];
            $method = $isMethod ? $config['method'] : false;
            $property = $isMethod ? false : $config['property'];

            $fieldAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Field');
            $accessAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Access');
            $publicAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\IsPublic');

            if (!$fieldAnnotation) {
                if ($accessAnnotation || $publicAnnotation) {
                    throw new InvalidArgumentException(\sprintf('The annotations "@Access" and/or "@Visible" defined on "%s" are only usable in addition of annotation "@Field"', $target));
                }
                continue;
            }

            if ($isMethod && !$method->isPublic()) {
                throw new InvalidArgumentException(\sprintf('The Annotation "@Field" can only be applied to public method. The method "%s" is not public.', $target));
            }

            // Ignore field with resolver when the type is an Input
            if ($fieldAnnotation->resolve && $isInput) {
                continue;
            }

            $propertyName = $target;
            $fieldType = $fieldAnnotation->type;
            $fieldConfiguration = [];
            if ($fieldType) {
                $resolvedType = self::resolveClassFromType($fieldType);
                if ($resolvedType) {
                    if ($isInput && !\in_array($resolvedType['type'], ['input', 'scalar', 'enum'])) {
                        throw new InvalidArgumentException(\sprintf('The type "%s" on "%s" is a "%s" not valid on an Input @Field. Only Input, Scalar and Enum are allowed.', $fieldType, $target, $resolvedType['type']));
                    }
                }

                $fieldConfiguration['type'] = $fieldType;
            }

            $fieldConfiguration += self::getDescriptionConfiguration($annotations, true);

            if (!$isInput) {
                $args = [];
                $args = self::getArgs($fieldAnnotation->args, $isMethod && !$fieldAnnotation->argsBuilder ? $method : null);

                if (!empty($args)) {
                    $fieldConfiguration['args'] = $args;
                }

                $propertyName = $fieldAnnotation->name ?: $propertyName;

                if ($fieldAnnotation->resolve) {
                    $fieldConfiguration['resolve'] = self::formatExpression($fieldAnnotation->resolve);
                } else {
                    if ($isMethod) {
                        $fieldConfiguration['resolve'] = self::formatExpression(\sprintf('@=call(%s.%s, %s)', $currentValue, $target, self::formatArgsForExpression($args)));
                    } elseif ($fieldAnnotation->name) {
                        $fieldConfiguration['resolve'] = self::formatExpression(\sprintf('@=call(%s.%s, [])', $currentValue, $target));
                    }
                }

                if ($fieldAnnotation->argsBuilder) {
                    if (\is_string($fieldAnnotation->argsBuilder)) {
                        $fieldConfiguration['argsBuilder'] = $fieldAnnotation->argsBuilder;
                    } elseif (\is_array($fieldAnnotation->argsBuilder)) {
                        list($builder, $builderConfig) = $fieldAnnotation->argsBuilder;
                        $fieldConfiguration['argsBuilder'] = ['builder' => $builder, 'config' => $builderConfig];
                    } else {
                        throw new InvalidArgumentException(\sprintf('The attribute "argsBuilder" on GraphQL annotation "@Field" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $target));
                    }
                }

                if ($fieldAnnotation->fieldBuilder) {
                    if (\is_string($fieldAnnotation->fieldBuilder)) {
                        $fieldConfiguration['builder'] = $fieldAnnotation->fieldBuilder;
                    } elseif (\is_array($fieldAnnotation->fieldBuilder)) {
                        list($builder, $builderConfig) = $fieldAnnotation->fieldBuilder;
                        $fieldConfiguration['builder'] = $builder;
                        $fieldConfiguration['builderConfig'] = $builderConfig ?: [];
                    } else {
                        throw new InvalidArgumentException(\sprintf('The attribute "argsBuilder" on GraphQL annotation "@Field" defined on "%s" must be a string or an array where first index is the builder name and the second is the config.', $target));
                    }
                } else {
                    if (!$fieldType) {
                        if ($isMethod) {
                            if ($method->hasReturnType()) {
                                try {
                                    $fieldConfiguration['type'] = self::resolveGraphqlTypeFromReflectionType($method->getReturnType(), 'type').'!';
                                } catch (\Exception $e) {
                                    throw new InvalidArgumentException(\sprintf('The attribute "type" on GraphQL annotation "@Field" is missing on method "%s" and cannot be auto-guessed from type hint "%s"', $target, (string) $method->getReturnType()));
                                }
                            } else {
                                throw new InvalidArgumentException(\sprintf('The attribute "type" on GraphQL annotation "@Field" is missing on method "%s" and cannot be auto-guessed as there is not return type hint.', $target));
                            }
                        } else {
                            try {
                                $fieldConfiguration['type'] = self::guessType($namespace, $annotations);
                            } catch (\Exception $e) {
                                throw new InvalidArgumentException(\sprintf('The attribute "type" on "@Field" defined on "%s" is required and cannot be auto-guessed : %s.', $target, $e->getMessage()));
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
            }

            $fields[$propertyName] = $fieldConfiguration;
        }

        return $fields;
    }

    /**
     * ArgTransformer
     *   Transform Arg type hint with enum as newObject(enumClassTypeHint, arg['a'])new EnumClass(arg['a'])
     *   Transform Arg type hint input as populate(InputClass, arg['a']).
     */

    /**
     * Return fields config from Provider methods.
     *
     * @param string $className
     * @param array  $methods
     * @param bool   $isMutation
     *
     * @return array
     */
    private static function getGraphqlFieldsFromProvider(string $className, array $methods, bool $isMutation = false)
    {
        $fields = [];
        foreach ($methods as $methodName => $config) {
            $annotations = $config['annotations'];
            $method = $config['method'];

            $annotation = self::getFirstAnnotationMatching($annotations, \sprintf('Overblog\\GraphQLBundle\\Annotation\\%s', $isMutation ? 'Mutation' : 'Query'));
            if (!$annotation) {
                continue;
            }

            $name = $annotation->name ?: $methodName;
            $type = $annotation->type;
            $args = self::getArgs($annotation->args, $method);
            if (!$type) {
                if ($method->hasReturnType()) {
                    try {
                        $type = self::resolveGraphqlTypeFromReflectionType($method->getReturnType(), 'type');
                    } catch (\Exception $e) {
                        throw new InvalidArgumentException(\sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed from type hint "%s"', $isMutation ? 'Mutation' : 'Query', $method, (string) $method->getReturnType()));
                    }
                } else {
                    throw new InvalidArgumentException(\sprintf('The attribute "type" on GraphQL annotation "@%s" is missing on method "%s" and cannot be auto-guessed as there is not return type hint.', $isMutation ? 'Mutation' : 'Query', $method));
                }
            }

            $resolve = \sprintf("@=call(service('%s').%s, %s)", self::formatNamespaceForExpression($className), $methodName, self::formatArgsForExpression($args));

            $fields[$name] = [
                'type' => $type,
                'args' => $args,
                'resolve' => $resolve,
            ];
        }

        return $fields;
    }

    /**
     * Get the config for description & deprecation reason.
     *
     * @param array $annotations
     * @param bool  $withDeprecation
     *
     * @return array
     */
    private static function getDescriptionConfiguration(array $annotations, bool $withDeprecation = false)
    {
        $config = [];
        $descriptionAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Description');
        if ($descriptionAnnotation) {
            $config['description'] = $descriptionAnnotation->value;
        }

        if ($withDeprecation) {
            $deprecatedAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Deprecated');
            if ($deprecatedAnnotation) {
                $config['deprecationReason'] = $deprecatedAnnotation->value;
            }
        }

        return $config;
    }

    /**
     * Get args config from an array of @Arg annotation or by auto-guessing if a method is provided.
     *
     * @param array             $args
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    private static function getArgs(array $args = null, \ReflectionMethod $method = null)
    {
        $config = [];
        if ($args && !empty($args)) {
            foreach ($args as $arg) {
                $config[$arg->name] = ['type' => $arg->type] + ($arg->description ? ['description' => $arg->description] : []);
            }
        } elseif ($method) {
            $config = self::guessArgs($method);
        }

        return $config;
    }

    private static function formatArgsForExpression(array $args)
    {
        $mapping = [];
        foreach ($args as $name => $config) {
            $mapping[] = \sprintf('%s: "%s"', $name, $config['type']);
        }

        return \sprintf('arguments({%s}, args)', \implode(', ', $mapping));
    }

    /**
     * Format an array of args to a list of arguments in an expression.
     *
     * @param array $args
     *
     * @return string
     */
    /*
    private static function formatArgsForExpression(array $args)
    {
        $resolvedArgs = [];
        foreach ($args as $name => $config) {
            $cleanedType = \str_replace(['[', ']', '!'], '', $config['type']);
            $definition = self::resolveClassFromType($cleanedType);
            $defaultFormat = \sprintf("args['%s']", $name);
            if (!$definition) {
                $resolvedArgs[] = $defaultFormat;
            } else {
                switch ($definition['type']) {
                    case 'input':
                    case 'enum':
                        $resolvedArgs[] = \sprintf("input('%s', args['%s'], '%s')", $config['type'], $name, $name);
                        break;
                    default:
                        $resolvedArgs[] = $defaultFormat;
                        break;
                }
            }
        }

        return sprintf("inputs(%s)", \implode(', ', $resolvedArgs));
    }
     */

    /**
     * Format a namespace to be used in an expression (double escape).
     *
     * @param string $namespace
     *
     * @return string
     */
    private static function formatNamespaceForExpression(string $namespace)
    {
        return \str_replace('\\', '\\\\', $namespace);
    }

    /**
     * Get the first annotation matching given class.
     *
     * @param array        $annotations
     * @param string|array $annotationClass
     *
     * @return mixed
     */
    private static function getFirstAnnotationMatching(array $annotations, $annotationClass)
    {
        if (\is_string($annotationClass)) {
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
     *
     * @param string $expression
     *
     * @return string
     */
    private static function formatExpression(string $expression)
    {
        return '@=' === \substr($expression, 0, 2) ? $expression : \sprintf('@=%s', $expression);
    }

    /**
     * Suffix a name if it is not already.
     *
     * @param string $name
     * @param string $suffix
     *
     * @return string
     */
    private static function suffixName(string $name, string $suffix)
    {
        return \substr($name, -\strlen($suffix)) === $suffix ? $name : \sprintf('%s%s', $name, $suffix);
    }

    /**
     * Try to guess a field type base on is annotations.
     *
     * @param string $namespace
     * @param array  $annotations
     *
     * @return string|false
     */
    private static function guessType(string $namespace, array $annotations)
    {
        $columnAnnotation = self::getFirstAnnotationMatching($annotations, 'Doctrine\ORM\Mapping\Column');
        if ($columnAnnotation) {
            $type = self::resolveTypeFromDoctrineType($columnAnnotation->type);
            $nullable = $columnAnnotation->nullable;
            if ($type) {
                return $nullable ? $type : \sprintf('%s!', $type);
            } else {
                throw new \Exception(\sprintf('Unable to auto-guess GraphQL type from Doctrine type "%s"', $columnAnnotation->type));
            }
        }

        $associationAnnotations = [
            'Doctrine\ORM\Mapping\OneToMany' => true,
            'Doctrine\ORM\Mapping\OneToOne' => false,
            'Doctrine\ORM\Mapping\ManyToMany' => true,
            'Doctrine\ORM\Mapping\ManyToOne' => false,
        ];

        $associationAnnotation = self::getFirstAnnotationMatching($annotations, \array_keys($associationAnnotations));
        if ($associationAnnotation) {
            $target = self::fullyQualifiedClassName($associationAnnotation->targetEntity, $namespace);
            $type = self::resolveTypeFromClass($target, 'type');

            if ($type) {
                $isMultiple = $associationAnnotations[\get_class($associationAnnotation)];
                if ($isMultiple) {
                    return \sprintf('[%s]!', $type);
                } else {
                    $isNullable = false;
                    $joinColumn = self::getFirstAnnotationMatching($annotations, 'Doctrine\ORM\Mapping\JoinColumn');
                    if ($joinColumn) {
                        $isNullable = $joinColumn->nullable;
                    }

                    return \sprintf('%s%s', $type, $isNullable ? '' : '!');
                }
            } else {
                throw new \Exception(\sprintf('Unable to auto-guess GraphQL type from Doctrine target class "%s" (check if the target class is a GraphQL type itself (with a @GQL\Type annotation).', $target));
            }
        }

        throw new InvalidArgumentException(\sprintf('No Doctrine ORM annotation found.'));
    }

    /**
     * Resolve a FQN from classname and namespace.
     *
     * @param string $className
     * @param string $namespace
     *
     * @return string
     */
    public static function fullyQualifiedClassName(string $className, string $namespace)
    {
        if (false === \strpos($className, '\\') && $namespace) {
            return $namespace.'\\'.$className;
        }

        return $className;
    }

    /**
     * Resolve a GraphqlType from a doctrine type.
     *
     * @param string $doctrineType
     *
     * @return string|false
     */
    private static function resolveTypeFromDoctrineType(string $doctrineType)
    {
        if (isset(self::$doctrineMapping[$doctrineType])) {
            return self::$doctrineMapping[$doctrineType];
        }

        switch ($doctrineType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return 'Int';
                break;
            case 'string':
            case 'text':
                return 'String';
                break;
            case 'bool':
            case 'boolean':
                return 'Boolean';
                break;
            case 'float':
            case 'decimal':
                return 'Float';
                break;
            default:
                return false;
        }
    }

    /**
     * Transform a method arguments from reflection to a list of GraphQL argument.
     *
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    private static function guessArgs(\ReflectionMethod $method)
    {
        $arguments = [];
        foreach ($method->getParameters() as $index => $parameter) {
            if (!$parameter->hasType()) {
                throw new InvalidArgumentException(\sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed as there is not type hint".', $index + 1, $parameter->getName(), $method->getName()));
            }

            try {
                $gqlType = self::resolveGraphqlTypeFromReflectionType($parameter->getType(), 'input');
            } catch (\Exception $e) {
                throw new InvalidArgumentException(\sprintf('Argument n°%s "$%s" on method "%s" cannot be auto-guessed : %s".', $index + 1, $parameter->getName(), $method->getName(), $e->getMessage()));
            }

            $argumentConfig = [];
            if ($parameter->isDefaultValueAvailable()) {
                $argumentConfig['defaultValue'] = $parameter->getDefaultValue();
            } else {
                $gqlType .= '!';
            }

            $argumentConfig['type'] = $gqlType;

            $arguments[$parameter->getName()] = $argumentConfig;
        }

        return $arguments;
    }

    /**
     * Try to guess a GraphQL type from a Reflected Type.
     *
     * @param \ReflectionType $type
     *
     * @return string
     */
    private static function resolveGraphqlTypeFromReflectionType(\ReflectionType $type, string $filterGraphqlType = null)
    {
        $stype = (string) $type;
        if ($type->isBuiltin()) {
            $gqlType = self::resolveTypeFromPhpType($stype);
            if (!$gqlType) {
                throw new \Exception(\sprintf('No corresponding GraphQL type found for builtin type "%s"', $stype));
            }
        } else {
            $gqlType = self::resolveTypeFromClass($stype, $filterGraphqlType);
            if (!$gqlType) {
                throw new \Exception(\sprintf('No corresponding GraphQL %s found for class "%s"', $filterGraphqlType ?: 'object', $stype));
            }
        }

        return $gqlType;
    }

    /**
     * Resolve a GraphQL Type from a class name.
     *
     * @param string $className
     * @param string $wantedType
     *
     * @return string|false
     */
    private static function resolveTypeFromClass(string $className, string $wantedType = null)
    {
        foreach (self::$classesMap as $gqlType => $config) {
            if ($config['class'] === $className) {
                if (!$wantedType || ($wantedType && $wantedType === $config['type'])) {
                    return $gqlType;
                }
            }
        }

        return false;
    }

    /**
     * Resolve a PHP class from a GraphQL type.
     *
     * @param string $type
     *
     * @return string|false
     */
    private static function resolveClassFromType(string $type)
    {
        return self::$classesMap[$type] ?? false;
    }

    /**
     * Convert a PHP Builtin type to a GraphQL type.
     *
     * @param string $phpType
     *
     * @return string
     */
    private static function resolveTypeFromPhpType(string $phpType)
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
                return false;
        }
    }
}
