<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Zend\Code\Reflection\PropertyReflection;

class AnnotationParser implements ParserInterface
{
    public static function getAnnotationReader()
    {
        if (!class_exists('\\Doctrine\\Common\\Annotations\\AnnotationReader') ||
            !class_exists('\\Doctrine\\Common\\Annotations\\AnnotationRegistry')
        ) {
            throw new \Exception('In order to use annotation, you need to require doctrine ORM');
        }

        $loader = require __DIR__.'/../../../../autoload.php';

        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
        \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__.'/../../Annotation/GraphQLAnnotation.php');
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();

        return $reader;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public static function parse(\SplFileInfo $file, ContainerBuilder $container)
    {
        $reader = self::getAnnotationReader();
        $container->addResource(new FileResource($file->getRealPath()));
        try {
            $fileContent = file_get_contents($file->getRealPath());

            $entityName = substr($file->getFilename(), 0, -4);
            if (preg_match('#namespace (.+);#', $fileContent, $namespace)) {
                $className = $namespace[1].'\\'.$entityName;
            } else {
                $className = $entityName;
            }

            $reflexionEntity = new \ReflectionClass($className);

            $annotations = $reader->getClassAnnotations($reflexionEntity);
            $annotations = self::parseAnnotation($annotations);

            $alias = self::getGraphQLAlias($annotations) ?: $entityName;
            $type = self::getGraphQLType($annotations);

            switch ($type) {
                case 'relay-connection':
                    return self::formatRelay($type, $alias, $annotations, $reflexionEntity->getProperties());
                case 'enum':
                    return self::formatEnumType($alias, $entityName, $reflexionEntity->getProperties());
                case 'custom-scalar':
                    return self::formatCustomScalarType($alias, $type, $className, $annotations);
                default:
                    return self::formatScalarType($alias, $type, $entityName, $reflexionEntity->getProperties());
            }
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * Get the graphQL alias.
     *
     * @param $annotation
     *
     * @return string|null
     */
    protected static function getGraphQLAlias($annotation)
    {
        if (array_key_exists('GraphQLAlias', $annotation) && !empty($annotation['GraphQLAlias']['name'])) {
            return $annotation['GraphQLAlias']['name'];
        }

        return null;
    }

    /**
     * Get the graphQL type.
     *
     * @param $annotation
     *
     * @return string
     */
    protected static function getGraphQLType($annotation)
    {
        if (array_key_exists('GraphQLType', $annotation) && !empty($annotation['GraphQLType']['type'])) {
            return $annotation['GraphQLType']['type'];
        }

        if (array_key_exists('GraphQLScalarType', $annotation) && !empty($annotation['GraphQLScalarType']['type'])) {
            return 'custom-scalar';
        }

        return 'object';
    }

    /**
     * @param string               $type
     * @param string               $alias
     * @param array                $classAnnotations
     * @param PropertyReflection[] $properties
     *
     * @return array
     *
     * @throws \Exception
     */
    protected static function formatRelay($type, $alias, $classAnnotations, $properties)
    {
        $reader = self::getAnnotationReader();

        $typesConfig = [
            $alias => [
                'type' => $type,
                'config' => [],
            ],
        ];

        if (!empty($classAnnotations['GraphQLNode'])) {
            $typesConfig[$alias]['config']['nodeType'] = $classAnnotations['GraphQLNode']['type'];
            $typesConfig[$alias]['config']['resolveNode'] = $classAnnotations['GraphQLNode']['resolve'];
        }

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyAnnotation = $reader->getPropertyAnnotations($property);
            $propertyAnnotation = self::parseAnnotation($propertyAnnotation);

            if (!empty($propertyAnnotation['GraphQLEdgeFields'])) {
                if (empty($typesConfig[$alias]['config']['edgeFields'])) {
                    $typesConfig[$alias]['config']['edgeFields'] = [];
                }

                $typesConfig[$alias]['config']['edgeFields'][$propertyName] = [
                    'type' => $propertyAnnotation['GraphQLEdgeFields']['type'],
                    'resolve' => $propertyAnnotation['GraphQLEdgeFields']['resolve'],
                ];
            } elseif (!empty($propertyAnnotation['GraphQLConnectionFields'])) {
                if (empty($typesConfig[$alias]['config']['connectionFields'])) {
                    $typesConfig[$alias]['config']['connectionFields'] = [];
                }

                $typesConfig[$alias]['config']['connectionFields'][$propertyName] = [
                    'type' => $propertyAnnotation['GraphQLConnectionFields']['type'],
                    'resolve' => $propertyAnnotation['GraphQLConnectionFields']['resolve'],
                ];
            }
        }

        return empty($typesConfig[$alias]['config'])
            ? []
            : $typesConfig;
    }

    /**
     * Format enum type.
     *
     * @param string                $alias
     * @param string                $entityName
     * @param \ReflectionProperty[] $properties
     *
     * @return array
     */
    protected static function formatEnumType($alias, $entityName, $properties)
    {
        $reader = self::getAnnotationReader();

        $typesConfig = [
            $alias => [
                'type' => 'enum',
                'config' => [
                    'description' => $entityName.' type',
                ],
            ],
        ];

        $values = [];
        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            $propertyName = $property->getName();

            $propertyAnnotation = $reader->getPropertyAnnotations($property);
            $propertyAnnotation = self::parseAnnotation($propertyAnnotation);

            $values[$propertyName] = [
                'value' => $propertyAnnotation,
            ];

            if (array_key_exists('GraphQLDescription', $propertyAnnotation) && !empty($test['GraphQLDescription']['description'])) {
                $values[$propertyName]['description'] = $test['GraphQLDescription']['description'];
            }
        }

        $typesConfig[$alias]['config']['values'] = $values;

        return $typesConfig;
    }

    /**
     * Format custom scalar type.
     *
     * @param string $alias
     * @param string $type
     * @param string $className
     * @param array  $annotations
     *
     * @return array
     */
    protected static function formatCustomScalarType($alias, $type, $className, $annotations)
    {
        if (array_key_exists('GraphQLScalarType', $annotations) && !empty($annotations['GraphQLScalarType']['type'])) {
            return [
                $alias => [
                    'type' => $type,
                    'config' => [
                        'scalarType' => $annotations['GraphQLScalarType']['type'],
                    ],
                ],
            ];
        }

        $config = [
            'serialize' => [$className, 'serialize'],
            'parseValue' => [$className, 'parseValue'],
            'parseLiteral' => [$className, 'parseLiteral'],
        ];

        return [
            $alias => [
                'type' => $type,
                'config' => $config,
            ],
        ];
    }

    /**
     * Format scalar type.
     *
     * @param string                $alias
     * @param string                $type
     * @param string                $entityName
     * @param \ReflectionProperty[] $properties
     *
     * @return array
     */
    protected static function formatScalarType($alias, $type, $entityName, $properties)
    {
        $reader = self::getAnnotationReader();

        $typesConfig = [
            $alias => [
                'type' => $type,
                'config' => [
                    'description' => $entityName.' type',
                    'fields' => [],
                ],
            ],
        ];

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyAnnotation = $reader->getPropertyAnnotations($property);
            $propertyAnnotation = self::parseAnnotation($propertyAnnotation);

            if (!$graphQlType = self::getGraphQLFieldType($propertyName, $propertyAnnotation)) {
                continue;
            }

            if ($graphQlAccessControl = self::getGraphQLAccessControl($propertyAnnotation)) {
                $graphQlType['access'] = $graphQlAccessControl;
            }

            if ($graphQlPublicControl = self::getGraphQLPublicControl($propertyAnnotation)) {
                $graphQlType['public'] = $graphQlPublicControl;
            }

            $typesConfig[$alias]['config']['fields'][$propertyName] = $graphQlType;
        }

        return empty($typesConfig[$alias]['config']['fields'])
            ? []
            : $typesConfig;
    }

    /**
     * Return the graphQL type for the named field.
     *
     * @param string $name
     * @param array  $annotation
     *
     * @return array|null
     */
    protected static function getGraphQLFieldType($name, $annotation)
    {
        if (!$type = self::getGraphQLScalarFieldType($name, $annotation)) {
            if (!$type = self::getGraphQLQueryField($annotation)) {
                if (!$type = self::getGraphQLMutationField($annotation)) {
                    return null;
                }
            }
        }

        return $type;
    }

    /**
     * Return the common field type, like ID, Int, String, and other user-created type.
     *
     * @param string $name
     * @param array  $annotation
     *
     * @return array|null
     */
    protected static function getGraphQLScalarFieldType($name, $annotation)
    {
        // Get the current type, depending on current annotation
        $type = $graphQLType = null;
        $nullable = $isMultiple = false;
        if (array_key_exists('GraphQLColumn', $annotation) && array_key_exists('type', $annotation['GraphQLColumn'])) {
            $annotation = $annotation['GraphQLColumn'];
            $type = $annotation['type'];
        } elseif (array_key_exists('GraphQLToMany', $annotation) && array_key_exists('target', $annotation['GraphQLToMany'])) {
            $annotation = $annotation['GraphQLToMany'];
            $type = $annotation['target'];
            $isMultiple = $nullable = true;
        } elseif (array_key_exists('GraphQLToOne', $annotation) && array_key_exists('target', $annotation['GraphQLToOne'])) {
            $annotation = $annotation['GraphQLToOne'];
            $type = $annotation['target'];
            $nullable = true;
        } elseif (array_key_exists('OneToMany', $annotation) && array_key_exists('targetEntity', $annotation['OneToMany'])) {
            $annotation = $annotation['OneToMany'];
            $type = $annotation['targetEntity'];
            $isMultiple = $nullable = true;
        } elseif (array_key_exists('OneToOne', $annotation) && array_key_exists('targetEntity', $annotation['OneToOne'])) {
            $annotation = $annotation['OneToOne'];
            $type = $annotation['targetEntity'];
            $nullable = true;
        } elseif (array_key_exists('ManyToMany', $annotation) && array_key_exists('targetEntity', $annotation['ManyToMany'])) {
            $annotation = $annotation['ManyToMany'];
            $type = $annotation['targetEntity'];
            $isMultiple = $nullable = true;
        } elseif (array_key_exists('ManyToOne', $annotation) && array_key_exists('targetEntity', $annotation['ManyToOne'])) {
            $annotation = $annotation['ManyToOne'];
            $type = $annotation['targetEntity'];
            $nullable = true;
        } elseif (array_key_exists('Column', $annotation) && array_key_exists('type', $annotation['Column'])) {
            $annotation = $annotation['Column'];
            $type = $annotation['type'];
        }

        if (!$type) {
            return null;
        }

        if (array_key_exists('nullable', $annotation)) {
            $nullable = 'true' == $annotation['nullable']
                ? true
                : false;
        }

        $type = explode('\\', $type);
        $type = $type[count($type) - 1];

        // Get the graphQL type representation
        // Specific case for ID and relation
        if ('id' === $name && 'integer' === $type) {
            $graphQLType = 'ID';
        } else {
            // Make the relation between doctrine Column type and graphQL type
            switch ($type) {
                case 'integer':
                    $graphQLType = 'Int';
                    break;
                case 'string':
                case 'text':
                    $graphQLType = 'String';
                    break;
                case 'bool':
                case 'boolean':
                    $graphQLType = 'Boolean';
                    break;
                case 'float':
                case 'decimal':
                    $graphQLType = 'Float';
                    break;
                default:
                    // No maching: considering is custom-scalar graphQL type
                    $graphQLType = $type;
            }
        }

        if ($isMultiple) {
            $graphQLType = '['.$graphQLType.']';
        }

        if (!$nullable) {
            $graphQLType .= '!';
        }

        return ['type' => $graphQLType];
    }

    /**
     * Get the graphql query formatted field.
     *
     * @param array $annotation
     *
     * @return array|null
     */
    protected static function getGraphQLQueryField($annotation)
    {
        if (!array_key_exists('GraphQLQuery', $annotation)) {
            return null;
        }

        $annotationQuery = $annotation['GraphQLQuery'];

        $ret = [
            'type' => $annotationQuery['type'],
        ];

        $method = $annotationQuery['method'];
        $args = $queryArgs = [];
        if (!empty($annotationQuery['input'])) {
            $annotationArgs = $annotationQuery['input'];
            if (!array_key_exists(0, $annotationArgs)) {
                $annotationArgs = [$annotationArgs];
            }

            foreach ($annotationArgs as $arg) {
                if (!empty($arg['target'])) {
                    if (!empty($arg['name']) && !empty($arg['type'])) {
                        $args[$arg['name']] = [
                            'type' => $arg['type'],
                        ];

                        if (!empty($arg['description'])) {
                            $args[$arg['name']]['description'] = $arg['description'];
                        }
                    }

                    $queryArgs[] = $arg['target'];
                } else {
                    $queryArgs[] = $arg;
                }
            }

            if (!empty($args)) {
                $ret['args'] = $args;
            }
        }

        if (!empty($queryArgs)) {
            $query = "'".$method."', [".implode(', ', $queryArgs).']';
        } else {
            $query = "'".$method."'";
        }

        $ret['resolve'] = '@=resolver('.$query.')';

        if (!empty($annotationQuery['argsBuilder'])) {
            $ret['argsBuilder'] = $annotationQuery['argsBuilder'];
        }

        return $ret;
    }

    /**
     * Get the formatted graphQL mutation field.
     *
     * @param array $annotation
     *
     * @return array
     */
    protected static function getGraphQLMutationField($annotation)
    {
        if (!array_key_exists('GraphQLMutation', $annotation)) {
            return self::getGraphQLRelayMutationField($annotation);
        }

        $annotation = $annotation['GraphQLMutation'];
        if (array_key_exists('args', $annotation)) {
            $mutate = "@=mutation('".$annotation['method']."', [".implode(', ', $annotation['args']).'])';
        } else {
            $mutate = "'".$annotation['method']."'";
        }

        return [
            'type' => $annotation['payload'],
            'resolve' => $mutate,
            'args' => $annotation['input'],
        ];
    }

    /**
     * Get the formatted graphQL relay mutation field.
     *
     * @param array $annotation
     *
     * @return array|null
     */
    protected static function getGraphQLRelayMutationField($annotation)
    {
        if (!array_key_exists('GraphQLRelayMutation', $annotation)) {
            return null;
        }

        $annotation = $annotation['GraphQLRelayMutation'];
        if (array_key_exists('args', $annotation)) {
            $mutate = "'".$annotation['method']."', [".implode(', ', $annotation['args']).']';
        } else {
            $mutate = "'".$annotation['method']."'";
        }

        return [
            'builder' => 'Relay::Mutation',
            'builderConfig' => [
                'inputType' => $annotation['input'][0],
                'payloadType' => $annotation['payload'],
                'mutateAndGetPayload' => '@=mutation('.$mutate.')',
            ],
        ];
    }

    /**
     * Get graphql access control annotation.
     *
     * @param $annotation
     *
     * @return null|string
     */
    protected static function getGraphQLAccessControl($annotation)
    {
        if (array_key_exists('GraphQLAccessControl', $annotation) && array_key_exists('method', $annotation['GraphQLAccessControl'])) {
            return '@='.$annotation['GraphQLAccessControl']['method'];
        }

        return null;
    }

    /**
     * Get graphql public control.
     *
     * @param $annotation
     *
     * @return null|string
     */
    protected static function getGraphQLPublicControl($annotation)
    {
        if (array_key_exists('GraphQLPublicControl', $annotation) && array_key_exists('method', $annotation['GraphQLPublicControl'])) {
            return '@='.$annotation['GraphQLPublicControl']['method'];
        }

        return null;
    }

    /**
     * Parse annotation.
     *
     * @param mixed $annotation
     *
     * @return array
     */
    protected static function parseAnnotation($annotations)
    {
        $returnAnnotation = [];
        foreach ($annotations as $index => $annotation) {
            if (!is_array($annotation)) {
                $index = explode('\\', get_class($annotation));
                $index = $index[count($index) - 1];
            }

            $returnAnnotation[$index] = [];

            foreach ($annotation as $indexAnnotation => $value) {
                $returnAnnotation[$index][$indexAnnotation] = $value;
            }
        }

        return $returnAnnotation;
    }
}
