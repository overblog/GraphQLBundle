<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class AnnotationParser implements ParserInterface
{
    private static $annotationReader = null;

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws UnexpectedValueException
     */
    public static function parse(\SplFileInfo $file, ContainerBuilder $container) : array
    {
        $container->addResource(new FileResource($file->getRealPath()));
        try {
            $fileContent = \file_get_contents($file->getRealPath());

            $shortClassName = \substr($file->getFilename(), 0, -4);
            if (\preg_match('#namespace (.+);#', $fileContent, $namespace)) {
                $className = $namespace[1] . '\\' . $shortClassName;
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
                $method = false;
                switch (get_class($classAnnotation)) {
                    case 'Overblog\GraphQLBundle\Annotation\Type':
                        $gqlTypes += self::getGraphqlType($shortClassName, $classAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations);
                        break;
                    case 'Overblog\GraphQLBundle\Annotation\InputType':
                        $gqlTypes += self::getGraphqlInputType($shortClassName, $classAnnotation, $classAnnotations, $propertiesAnnotations);
                        break;
                    case 'Overblog\GraphQLBundle\Annotation\Scalar':
                        $gqlTypes += self::getGraphqlScalar($shortClassName, $className, $classAnnotation, $classAnnotations);
                        break;
                    case 'Overblog\GraphQLBundle\Annotation\Enum':
                        $gqlTypes += self::getGraphqlEnum($shortClassName, $classAnnotation, $classAnnotations, $reflexionEntity->getConstants());
                        break;
                    case 'Overblog\GraphQLBundle\Annotation\Union':
                        $gqlTypes += self::getGraphqlUnion($shortClassName, $classAnnotation, $classAnnotations);
                        break;
                    case 'Overblog\GraphQLBundle\Annotation\TypeInterface':
                        $gqlTypes += self::getGraphqlInterface($shortClassName, $classAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations);
                        break;
                }

                if ($method) {
                    $gqlTypes += self::$method($shortClassName, $classAnnotation, $propertiesAnnotations);
                }
            }

            return $gqlTypes;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(\sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * Retrieve annotation reader
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
     * Create a GraphQL Type configuration from annotations on class, properties and methods
     * 
     * @param string       $shortClassName
     * @param Annotation   $typeAnnotation
     * @param Annotation[] $classAnnotations
     * @param Annotation[] $propertiesAnnotations
     * @param Annotation[] $methodsAnnotations
     * 
     * @return array
     */
    private static function getGraphqlType(string $shortClassName, $typeAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations)
    {
        $typeName = $typeAnnotation->name ? : $shortClassName;
        $typeConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($propertiesAnnotations);
        $fields += self::getGraphqlFieldsFromAnnotations($methodsAnnotations, false, true);

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

        return [$typeName => ['type' => $typeAnnotation->isRelay ? 'relay-mutation-payload' : 'object', 'config' => $typeConfiguration]];
    }

    /**
     * Create a GraphQL Interface type configuration from annotations on properties
     * 
     * @param string       $shortClassName
     * @param Annotation   $interfaceAnnotation
     * @param Annotation[] $propertiesAnnotations
     * 
     * @return array
     */
    private static function getGraphqlInterface(string $shortClassName, $interfaceAnnotation, $classAnnotations, $propertiesAnnotations, $methodsAnnotations)
    {
        $interfaceName = $interfaceAnnotation->name ? : $shortClassName;
        $interfaceConfiguration = [];

        $fields = self::getGraphqlFieldsFromAnnotations($propertiesAnnotations);
        $fields += self::getGraphqlFieldsFromAnnotations($methodsAnnotations, false, true);

        if (empty($fields)) {
            return [];
        }

        $interfaceConfiguration['fields'] = $fields;
        $interfaceConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return [$interfaceName => ['type' => 'interface', 'config' => $interfaceConfiguration]];
    }

    /**
     * Create a GraphQL Input type configuration from annotations on properties
     * 
     * @param string       $shortClassName
     * @param Annotation   $inputAnnotation
     * @param Annotation[] $propertiesAnnotations
     * 
     * @return array
     */
    private static function getGraphqlInputType(string $shortClassName, $inputAnnotation, $classAnnotations, $propertiesAnnotations)
    {
        $inputName = $inputAnnotation->name ? : self::suffixName($shortClassName, 'Input');
        $inputConfiguration = [];
        $fields = self::getGraphqlFieldsFromAnnotations($propertiesAnnotations, true);

        if (empty($fields)) {
            return [];
        }

        $inputConfiguration['fields'] = $fields;
        $inputConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return [$inputName => ['type' => $inputAnnotation->isRelay ? 'relay-mutation-input' : 'input-object', 'config' => $inputConfiguration]];
    }

    /**
     * Get a Graphql scalar configuration from given scalar annotation 
     * 
     * @param string     $shortClassName
     * @param string     $className
     * @param Annotation $scalarAnnotation
     * 
     * @return array
     */
    private static function getGraphqlScalar(string $shortClassName, string $className, $scalarAnnotation, $classAnnotations)
    {
        $scalarName = $scalarAnnotation->name ? : $shortClassName;
        $scalarConfiguration = [];

        if ($scalarAnnotation->scalarType) {
            $scalarConfiguration['scalarType'] = self::formatExpression($scalarAnnotation->scalarType);
        } else {
            $scalarConfiguration = [
                'serialize' => [$className, 'serialize'],
                'parseValue' => [$className, 'parseValue'],
                'parseLiteral' => [$className, 'parseLiteral']
            ];
        }

        $scalarConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return [$scalarName => ['type' => 'custom-scalar', 'config' => $scalarConfiguration]];
    }

    /**
     * Get a Graphql Enum configuration from given enum annotation 
     * 
     * @param string     $shortClassName
     * @param Annotation $enumAnnotation
     * 
     * @return array
     */
    private static function getGraphqlEnum($shortClassName, $enumAnnotation, $classAnnotations, $constants)
    {
        $enumName = $enumAnnotation->name ? : self::suffixName($shortClassName, 'Enum');
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
        $enumConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return [$enumName => ['type' => 'enum', 'config' => $enumConfiguration]];
    }

    /**
     * Get a Graphql Union configuration from given union annotation 
     * 
     * @param string     $shortClassName
     * @param Annotation $unionAnnotation
     * 
     * @return array
     */
    private static function getGraphqlUnion($shortClassName, $unionAnnotation, $classAnnotations)
    {
        $unionName = $unionAnnotation->name ? : $shortClassName;
        $unionConfiguration = ['types' => $unionAnnotation->types];
        $unionConfiguration += self::getDescriptionConfiguration($classAnnotations);

        return [$unionName => ['type' => 'union', 'config' => $unionConfiguration]];
    }

    /**
     * Create Graphql fields configuration based on annotation
     * 
     * @param Annotation[] $annotations
     * @param boolean      $isInput
     * @param boolean      $isMethod
     * 
     * @return array
     */
    private static function getGraphqlFieldsFromAnnotations($annotations, $isInput = false, $isMethod = false) : array
    {
        $fields = [];
        foreach ($annotations as $target => $annotations) {
            $fieldAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Field');
            $accessAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\Access');
            $publicAnnotation = self::getFirstAnnotationMatching($annotations, 'Overblog\GraphQLBundle\Annotation\IsPublic');

            if (!$fieldAnnotation) {
                if ($accessAnnotation || $publicAnnotation) {
                    throw new InvalidArgumentException(\sprintf('The annotations "@Access" and/or "@Visible" defined on "%s" are only usable in addition of annotation @Field', $target));
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

                    $args = array_map(function ($a) {
                        return sprintf("args['%s']", $a);
                    }, array_keys($args));
                }

                $propertyName = $fieldAnnotation->name ? : $propertyName;

                if ($fieldAnnotation->resolve) {
                    $fieldConfiguration['resolve'] = self::formatExpression($fieldAnnotation->resolve);
                } else {
                    if ($isMethod) {
                        $fieldConfiguration['resolve'] = self::formatExpression(sprintf("value_resolver([%s], '%s')", implode(", ", $args), $target));
                    }
                }

                if (!$isMethod && $fieldAnnotation->name && !$fieldAnnotation->resolve) {
                    throw new \UnexpectedValueException(\sprintf('The attribute "name" on Graphql annotation "@Field" define on "%s" is not allowed on properties without a configured resolver', $target));
                }

                if ($fieldAnnotation->argsBuilder) {
                    if (is_string($fieldAnnotation->argsBuilder)) {
                        $fieldConfiguration['argsBuilder'] = $fieldAnnotation->argsBuilder;
                    } elseif (is_array($fieldAnnotation->argsBuilder)) {
                        list($builder, $builderConfig) = $fieldAnnotation->argsBuilder;
                        $fieldConfiguration['argsBuilder'] = ['builder' => $builder, 'config' => $builderConfig];
                    } else {
                        throw new \UnexpectValueException(\sprintf('The attribute "argsBuilder" on Graphql annotation "@Field" defined on %s must be a string or an array where first index is the builder name and the second is the config.', $target));
                    }
                }

                if ($fieldAnnotation->fieldBuilder) {
                    if (is_string($fieldAnnotation->fieldBuilder)) {
                        $fieldConfiguration['builder'] = $fieldAnnotation->fieldBuilder;
                    } elseif (is_array($fieldAnnotation->fieldBuilder)) {
                        list($builder, $builderConfig) = $fieldAnnotation->fieldBuilder;
                        $fieldConfiguration['builder'] = $builder;
                        $fieldConfiguration['builderConfig'] = $builderConfig ? : [];
                    } else {
                        throw new \UnexpectValueException(\sprintf('The attribute "argsBuilder" on Graphql annotation "@Field" defined on %s must be a string or an array where first index is the builder name and the second is the config.', $target));
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
     * Get the first annotation matching given class
     * 
     * @param Annotation[] $annotations
     * @param string       $annotationClass
     * 
     * @return Annotation|false
     */
    private static function getFirstAnnotationMatching($annotations, $annotationClass)
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClass) {
                return $annotation;
            }
        }

        return false;
    }

    /**
     * Get the config for description & deprecation reason
     * 
     * @param Annotation $descriptionAnnotation
     * 
     * @return array
     */
    private static function getDescriptionConfiguration($annotations, $withDeprecation = false)
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
     * Format an expression (ie. add "@=" if not set)
     * 
     * @param string $expression
     * 
     * @return string
     */
    private static function formatExpression($expression)
    {
        return substr($expression, 0, 2) === '@=' ? $expression : sprintf('@=%s', $expression);
    }

    /**
     * Suffix a name if it is not already
     * 
     * @param string $name
     * @param string $suffix
     * 
     * @return string
     */
    private static function suffixName(string $name, string $suffix)
    {
        return substr($name, strlen($suffix)) === $suffix ? $name : sprintf("%s%s", $name, $suffix);
    }
}
