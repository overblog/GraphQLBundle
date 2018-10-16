<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Overblog\GraphQLBundle\Annotation\Enum as AnnotationEnum;
use Overblog\GraphQLBundle\Annotation\InputType as AnnotationInputType;
use Overblog\GraphQLBundle\Annotation\Scalar as AnnotationScalar;
use Overblog\GraphQLBundle\Annotation\Type as AnnotationType;
use Overblog\GraphQLBundle\Annotation\TypeInterface as AnnotationInterface;
use Overblog\GraphQLBundle\Annotation\Union as AnnotationUnion;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class AnnotationParser implements PreParserInterface
{
    private static $annotationReader = null;
    private static $classesMap = [];

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function parse(\SplFileInfo $file, ContainerBuilder $container): array
    {
        return self::proccessFile($file, $container);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function preParse(\SplFileInfo $file, ContainerBuilder $container): void
    {
        self::proccessFile($file, $container, true);
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
    public static function proccessFile(\SplFileInfo $file, ContainerBuilder $container, bool $resolveClassMap = false): array
    {
        $container->addResource(new FileResource($file->getRealPath()));
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

            $properties = $reflexionEntity->getProperties();

            $propertiesAnnotations = [];
            foreach ($properties as $property) {
                $propertiesAnnotations[$property->getName()] = self::getAnnotationReader()->getPropertyAnnotations($property);
            }

            $methods = $reflexionEntity->getMethods();
            $methodsAnnotations = [];
            foreach ($methods as $method) {
                $methodsAnnotations[$method->getName()] = self::getAnnotationReader()->getMethodAnnotations($method);
            }

            $gqlTypes = [];

            foreach ($classAnnotations as $classAnnotation) {
                $gqlName = $gqlConfiguration = $gqlType = false;
                switch (true) {
                    case $classAnnotation instanceof AnnotationType:
                        $gqlType = 'type';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlType($classAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations, $namespace);
                        }
                        break;
                    case $classAnnotation instanceof AnnotationInputType:
                        $gqlType = 'input';
                        $gqlName = $classAnnotation->name ?: self::suffixName($shortClassName, 'Input');
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlInputType($classAnnotation, $classAnnotations, $propertiesAnnotations, $namespace);
                        }
                        break;
                    case $classAnnotation instanceof AnnotationScalar:
                        $gqlType = 'scalar';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlScalar($className, $classAnnotation, $classAnnotations);
                        }
                        break;
                    case $classAnnotation instanceof AnnotationEnum:
                        $gqlType = 'enum';
                        $gqlName = $classAnnotation->name ?: self::suffixName($shortClassName, 'Enum');
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlEnum($classAnnotation, $classAnnotations, $reflexionEntity->getConstants());
                        }
                        break;
                    case $classAnnotation instanceof AnnotationUnion:
                        $gqlType = 'union';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlUnion($classAnnotation, $classAnnotations);
                        }
                        break;
                    case $classAnnotation instanceof AnnotationInterface:
                        $gqlType = 'interface';
                        if (!$resolveClassMap) {
                            $gqlConfiguration = self::getGraphqlInterface($classAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations, $namespace);
                        }
                        break;
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
            throw new InvalidArgumentException(\sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
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
     * @param AnnotationType $typeAnnotation
     * @param array          $classAnnotations
     * @param array          $propertiesAnnotations
     * @param array          $methodsAnnotations
     * @param string         $namespace
     *
     * @return array
     */
    private static function getGraphqlType(AnnotationType $typeAnnotation, array $classAnnotations, array $propertiesAnnotations, array $methodsAnnotations, string $namespace)
    {
        $typeConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $propertiesAnnotations);
        $fields += self::getGraphqlFieldsFromAnnotations($namespace, $methodsAnnotations, false, true);

        if (empty($fields)) {
            return [];
        }

        $typeConfiguration['fields'] = $fields;

        $publicAnnotation = self::getFirstAnnotationMatching($classAnnotations, 'Overblog\GraphQLBundle\Annotation\IsPublic');
        if ($publicAnnotation) {
            $typeConfiguration['fieldsDefaultPublic'] = self::formatExpression($publicAnnotation->value);
        }

        $accessAnnotation = self::getFirstAnnotationMatching($classAnnotations, 'Overblog\GraphQLBundle\Annotation\Access');
        if ($accessAnnotation) {
            $typeConfiguration['fieldsDefaultAccess'] = self::formatExpression($accessAnnotation->value);
        }

        $typeConfiguration += self::getDescriptionConfiguration($classAnnotations);
        if ($typeAnnotation->interfaces) {
            $typeConfiguration['interfaces'] = $typeAnnotation->interfaces;
        }

        return ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration];
    }

    /**
     * Create a GraphQL Interface type configuration from annotations on properties.
     *
     * @param string              $shortClassName
     * @param AnnotationInterface $interfaceAnnotation
     * @param array               $propertiesAnnotations
     * @param string              $namespace
     *
     * @return array
     */
    private static function getGraphqlInterface(AnnotationInterface $interfaceAnnotation, array $classAnnotations, array $propertiesAnnotations, array $methodsAnnotations, string $namespace)
    {
        $interfaceConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $propertiesAnnotations);
        $fields += self::getGraphqlFieldsFromAnnotations($namespace, $methodsAnnotations, false, true);

        if (empty($fields)) {
            return [];
        }

        $interfaceConfiguration['fields'] = $fields;
        $interfaceConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => 'interface', 'config' => $interfaceConfiguration];
    }

    /**
     * Create a GraphQL Input type configuration from annotations on properties.
     *
     * @param string              $shortClassName
     * @param AnnotationInputType $inputAnnotation
     * @param array               $propertiesAnnotations
     * @param string              $namespace
     *
     * @return array
     */
    private static function getGraphqlInputType(AnnotationInputType $inputAnnotation, array $classAnnotations, array $propertiesAnnotations, string $namespace)
    {
        $inputConfiguration = [];
        $fields = self::getGraphqlFieldsFromAnnotations($namespace, $propertiesAnnotations, true);

        if (empty($fields)) {
            return [];
        }

        $inputConfiguration['fields'] = $fields;
        $inputConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration];
    }

    /**
     * Get a Graphql scalar configuration from given scalar annotation.
     *
     * @param string           $shortClassName
     * @param string           $className
     * @param AnnotationScalar $scalarAnnotation
     * @param array            $classAnnotations
     *
     * @return array
     */
    private static function getGraphqlScalar(string $className, AnnotationScalar $scalarAnnotation, array $classAnnotations)
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
     * @param string         $shortClassName
     * @param AnnotationEnum $enumAnnotation
     * @param array          $classAnnotations
     * @param array          $constants
     *
     * @return array
     */
    private static function getGraphqlEnum(AnnotationEnum $enumAnnotation, array $classAnnotations, array $constants)
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
     * @param string          $shortClassName
     * @param AnnotationUnion $unionAnnotation
     * @param array           $classAnnotations
     *
     * @return array
     */
    private static function getGraphqlUnion(AnnotationUnion $unionAnnotation, array $classAnnotations): array
    {
        $unionConfiguration = ['types' => $unionAnnotation->types];
        $unionConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return ['type' => 'union', 'config' => $unionConfiguration];
    }

    /**
     * Create Graphql fields configuration based on annotation.
     *
     * @param string $namespace
     * @param array  $annotations
     * @param bool   $isInput
     * @param bool   $isMethod
     *
     * @return array
     */
    private static function getGraphqlFieldsFromAnnotations(string $namespace, array $annotations, bool $isInput = false, bool $isMethod = false): array
    {
        $fields = [];
        foreach ($annotations as $target => $annotations) {
            $fieldAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Field');
            $accessAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Access');
            $publicAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\IsPublic');

            if (!$fieldAnnotation) {
                if ($accessAnnotation || $publicAnnotation) {
                    throw new InvalidArgumentException(\sprintf('The annotations "@Access" and/or "@Visible" defined on "%s" are only usable in addition of annotation "@Field"', $target));
                }
                continue;
            }

            // Ignore field with resolver when the type is an Input
            if ($fieldAnnotation->resolve && $isInput) {
                continue;
            }

            $propertyName = $target;
            $fieldType = $fieldAnnotation->type;
            $fieldConfiguration = [];
            if ($fieldType) {
                $fieldConfiguration['type'] = $fieldType;
            }

            $fieldConfiguration += self::getDescriptionConfiguration($annotations, true);

            if (!$isInput) {
                $args = [];
                if ($fieldAnnotation->args) {
                    foreach ($fieldAnnotation->args as $annotationArg) {
                        $args[$annotationArg->name] = ['type' => $annotationArg->type] + ($annotationArg->description ? ['description' => $annotationArg->description] : []);
                    }

                    if (!empty($args)) {
                        $fieldConfiguration['args'] = $args;
                    }

                    $args = \array_map(function ($a) {
                        return \sprintf("args['%s']", $a);
                    }, \array_keys($args));
                }

                $propertyName = $fieldAnnotation->name ?: $propertyName;

                if ($fieldAnnotation->resolve) {
                    $fieldConfiguration['resolve'] = self::formatExpression($fieldAnnotation->resolve);
                } else {
                    if ($isMethod) {
                        $fieldConfiguration['resolve'] = self::formatExpression(\sprintf('value.%s(%s)', $target, \implode(', ', $args)));
                    } elseif ($fieldAnnotation->name) {
                        $fieldConfiguration['resolve'] = self::formatExpression(\sprintf('value.%s', $target));
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
                            throw new InvalidArgumentException(\sprintf('The attribute "type" on GraphQL annotation "@Field" is missing on method "%s" and cannot be auto-guesses.', $target));
                        } else {
                            try {
                                $fieldConfiguration['type'] = self::guessType($namespace, $annotations);
                            } catch (\Exception $e) {
                                throw new InvalidArgumentException(\sprintf('The attribute "type" on "@Field" defined on "%s" cannot be auto-guessed : %s.', $target, $e->getMessage()));
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
        return \substr($name, \strlen($suffix)) === $suffix ? $name : \sprintf('%s%s', $name, $suffix);
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
     * Resolve a Graphql Type from a class name.
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
}
