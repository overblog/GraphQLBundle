<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\EnumValueDefinitionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\Visitor;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;

class OverblogGraphQLTypesExtension extends Extension
{
    const NAME_TO_KEY = '__key';

    private $yamlParser;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('overblog_graphql_types.config', $config);
    }

    public function containerPrependExtensionConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = array_merge(
            $this->typesConfigsMappingFromConfig($config, $container),
            $this->typesConfigsMappingFromBundles($container)
        );

        // treats mappings
        foreach ($typesMappings as $params) {
            $this->prependExtensionConfigFromFiles($params['type'], $params['files'], $container);
        }
    }

    /**
     * @param $type
     * @param SplFileInfo[]    $files
     * @param ContainerBuilder $container
     */
    private function prependExtensionConfigFromFiles($type, $files, ContainerBuilder $container)
    {
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            switch ($type) {
                case 'yml':
                    $typeConfig = $this->typesConfigFromYml($file, $container);
                    break;
                case 'xml':
                    $typeConfig = $this->typesConfigFromXml($file, $container);
                    break;
                default:
                    $typeConfig = $this->typesConfigFromGraphqls($file, $container);
                    break;
            }

            $container->prependExtensionConfig($this->getAlias(), $typeConfig);
        }
    }

    private function typesConfigFromXml(SplFileInfo $file, ContainerBuilder $container)
    {
        $typesConfig = [];

        try {
            //@todo fix xml validateSchema
            $xml = XmlUtils::loadFile($file->getRealPath());
            foreach ($xml->documentElement->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }
                $values = XmlUtils::convertDomElementToArray($node);
                $typesConfig = array_merge($typesConfig, $values);
            }
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }

    private function typesConfigFromGraphqls(SplFileInfo $file, ContainerBuilder $container)
    {
        try {
            $typesConfig = $this->parseSchemaToOverblogGraphQLTypesConfigArray($file->getContents());
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (SyntaxError $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid GraphQL schema language.', $file), 0, $e);
        }

        return $typesConfig;
    }

    private function typesConfigFromYml(SplFileInfo $file, ContainerBuilder $container)
    {
        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        try {
            $typesConfig = $this->yamlParser->parse($file->getContents());
            $container->addResource(new FileResource($file->getRealPath()));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        }

        return $typesConfig;
    }

    private function typesConfigsMappingFromConfig(array $config, ContainerBuilder $container)
    {
        $typesMappings = [];
        // from config
        if (!empty($config['definitions']['mappings']['types'])) {
            $typesMappings = array_filter(array_map(
                function (array $typeMapping) use ($container) {
                    $params = $this->detectConfigFiles($container, $typeMapping['dir'],  $typeMapping['type']);

                    return $params;
                },
                $config['definitions']['mappings']['types']
            ));
        }

        return $typesMappings;
    }

    private function typesConfigsMappingFromBundles(ContainerBuilder $container)
    {
        $typesMappings = [];
        $bundles = $container->getParameter('kernel.bundles');

        // auto detect from bundle
        foreach ($bundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $bundleDir = dirname($bundle->getFileName());

            $configPath = $bundleDir.'/'.$this->getMappingResourceConfigDirectory();
            $params = $this->detectConfigFiles($container, $configPath);

            if (null !== $params) {
                $typesMappings[] = $params;
            }
        }

        return $typesMappings;
    }

    private function detectConfigFiles(ContainerBuilder $container, $configPath, $type = null)
    {
        // add the closest existing directory as a resource
        $resource = $configPath;
        while (!is_dir($resource)) {
            $resource = dirname($resource);
        }
        $container->addResource(new FileResource($resource));

        $extension = $this->getMappingResourceExtension();

        $types = null === $type ? ['yml', 'xml', 'graphqls'] : [$type];

        foreach ($types as $type) {
            $finder = new Finder();
            try {
                $finder->files()->in($configPath)->name('*.'.$extension.'.'.$type);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
            if ($finder->count() > 0) {
                return [
                    'type' => $type,
                    'files' => $finder,
                ];
            }
        }

        return;
    }

    private function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/graphql';
    }

    private function getMappingResourceExtension()
    {
        return 'types';
    }

    public function getAliasPrefix()
    {
        return 'overblog_graphql';
    }

    public function getAlias()
    {
        return $this->getAliasPrefix().'_types';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new TypesConfiguration();
    }

    private function parseSchemaToOverblogGraphQLTypesConfigArray($sourceString) {
        $ast = Parser::parse($sourceString, ['noLocation' => true, 'noSource' => true]);

        // fixme: here there be dragons.
        //
        // - the commented bits are almost definitely not needed.
        // - the uncommented bits that refer to self:: methods are likely to be needed at some point, but
        //      haven't been converted yet.

        $types = Visitor::visit($ast, array(
            'leave' => array(
                NodeKind::NAME => function($node) {return '' . $node->value;},
                NodeKind::VARIABLE => function($node) {return '$' . $node->name;},

                NodeKind::DOCUMENT => function(DocumentNode $node) {
                    return $node->definitions;
                },

                NodeKind::OPERATION_DEFINITION => function(OperationDefinitionNode $node) {
                    $op = $node->operation;
                    $name = $node->name;
                    $varDefs = self::wrap('(', self::join($node->variableDefinitions, ', '), ')');
                    $directives = self::join($node->directives, ' ');
                    $selectionSet = $node->selectionSet;
                    // Anonymous queries with no directives or variable definitions can use
                    // the query short form.
                    return !$name && !$directives && !$varDefs && $op === 'query'
                        ? $selectionSet
                        : self::join([$op, self::join([$name, $varDefs]), $directives, $selectionSet], ' ');
                },
                NodeKind::VARIABLE_DEFINITION => function(VariableDefinitionNode $node) {
                    return $node->variable . ': ' . $node->type . self::wrap(' = ', $node->defaultValue);
                },
                //        NodeKind::SELECTION_SET => function(SelectionSet $node) {
                //            return self::block($node->selections);
                //        },
                //        NodeKind::FIELD => function(Field $node) {
                //            return self::join([
                //                self::wrap('', $node->alias, ': ') . $node->name . self::wrap('(', self::join($node->arguments, ', '), ')'),
                //                self::join($node->directives, ' '),
                //                $node->selectionSet
                //            ], ' ');
                //        },
                //        NodeKind::ARGUMENT => function(Argument $node) {
                //            return $node->name . ': ' . $node->value;
                //        },

                // Fragments
                //        NodeKind::FRAGMENT_SPREAD => function(FragmentSpread $node) {
                //            return '...' . $node->name . self::wrap(' ', self::join($node->directives, ' '));
                //        },
                //        NodeKind::INLINE_FRAGMENT => function(InlineFragment $node) {
                //            return self::join([
                //                "...",
                //                self::wrap('on ', $node->typeCondition),
                //                self::join($node->directives, ' '),
                //                $node->selectionSet
                //            ], ' ');
                //        },
                NodeKind::FRAGMENT_DEFINITION => function(FragmentDefinitionNode $node) {
                    return "fragment {$node->name} on {$node->typeCondition} "
                        . self::wrap('', self::join($node->directives, ' '), ' ')
                        . $node->selectionSet;
                },

                // Value
                NodeKind::INT => function(IntValueNode  $node) {return $node->value;},
                NodeKind::FLOAT => function(FloatValueNode $node) {return $node->value;},
                NodeKind::STRING => function(StringValueNode $node) {return $node->value;},
                NodeKind::BOOLEAN => function(BooleanValueNode $node) {return $node->value;},
                NodeKind::ENUM => function(EnumValueNode $node) {return $node->value;},
                // todo: these two probably won't work as is - they need to be tested with actual data
                NodeKind::LST => function(ListValueNode $node) {return $node->values;},
                NodeKind::OBJECT => function(ObjectValueNode $node) {return $node->fields;},
                // todo: is this also a valid value for the purposes of defaultValue?
                //        NodeKind::OBJECT_FIELD => function(ObjectField $node) {return $node->name . ': ' . $node->value;},
                //
                //        // Directive
                //        NodeKind::DIRECTIVE => function(Directive $node) {
                //            return '@' . $node->name . self::wrap('(', self::join($node->arguments, ', '), ')');
                //        },

                // Type
                NodeKind::NAMED_TYPE => function(NamedTypeNode $node) {return $node->name;},
                NodeKind::LIST_TYPE => function(ListTypeNode $node) {return '[' . $node->type . ']';},
                NodeKind::NON_NULL_TYPE => function(NonNullTypeNode $node) {return $node->type . '!';},

                // Type System Definitions
                //        NodeKind::SCHEMA_DEFINITION => function(SchemaDefinition $def) {return 'schema ' . self::block($def->operationTypes);},
                //        NodeKind::OPERATION_TYPE_DEFINITION => function(OperationTypeDefinition $def) {return $def->operation . ': ' . $def->type;},

                NodeKind::SCALAR_TYPE_DEFINITION => function(ScalarTypeDefinitionNode $def) {
                    // todo: boo, it seems that graphql-php-generator does not support custom scalars.
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'scalar'
                    ];
                },
                NodeKind::OBJECT_TYPE_DEFINITION => function(ObjectTypeDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'object',
                        'config' => [
                            'fields' => $def->fields,
                            'interfaces' => $def->interfaces,
                            'description' => $def->description
                        ]
                    ];
                },
                NodeKind::FIELD_DEFINITION => function(FieldDefinitionNode $def) {
                    $description = $def->description;
                    $resolver = null;
                    if (strpos($def->description, '@@resolver') === 0) {
                        $newLineStart = strpos($description, "\n");
                        $resolver = '@=resolver'.substr($description, 10, $newLineStart - 10);
                        $description = substr($description, $newLineStart + 1);
                    }

                    $config = [
                        self::NAME_TO_KEY => $def->name,
                        'type' => $def->type,
                        'args' => $def->arguments,
                        'description' => $description
                    ];

                    if ($resolver) {
                        $config['resolve'] = $resolver;
                    }

                    return $config;
                },
                NodeKind::INPUT_VALUE_DEFINITION => function(InputValueDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => $def->type,
                        'defaultValue' => $def->defaultValue,
                        'description' => $def->description
                    ];
                },
                NodeKind::INTERFACE_TYPE_DEFINITION => function(InterfaceTypeDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'interface',
                        'config' => [
                            'fields' => $def->fields,
                            'description' => $def->description,
                            'resolveType' => "@=resolver('resolve_type', [value])"
                        ]
                    ];
                },
                NodeKind::UNION_TYPE_DEFINITION => function(UnionTypeDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'union',
                        'config' => [
                            'types' => $def->types,
                            'description' => $def->description,
                            'resolveType' => "@=resolver('resolve_type', [value])"
                        ],
                    ];
                },
                NodeKind::ENUM_TYPE_DEFINITION => function(EnumTypeDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'enum',
                        'config' => [
                            'values' => $def->values,
                            'description' => $def->description
                        ]
                    ];
                },
                NodeKind::ENUM_VALUE_DEFINITION => function(EnumValueDefinitionNode $def) {
                    $description = $def->description;
                    $value = null;
                    if (strpos($def->description, '@@value(') === 0) {
                        $newLineStart = strpos($description, "\n");
                        $value = substr($description, 8, $newLineStart - 9);
                        $description = substr($description, $newLineStart + 1);
                    }

                    $config = [
                        'name' => $def->name,
                        'description' => $description
                    ];

                    if ($value !== null) {
                        $config['value'] = $value;
                    }

                    return $config;
                },
                NodeKind::INPUT_OBJECT_TYPE_DEFINITION => function(InputObjectTypeDefinitionNode $def) {
                    return [
                        self::NAME_TO_KEY => $def->name,
                        'type' => 'input-object',
                        'config' => [
                            'fields' => $def->fields,
                            'description' => $def->description
                        ]
                    ];
                },
                NodeKind::TYPE_EXTENSION_DEFINITION => function(TypeExtensionDefinitionNode $def) {return "extend {$def->definition}";},
                NodeKind::DIRECTIVE_DEFINITION => function(DirectiveDefinitionNode $def) {
                    return 'directive @' . $def->name . self::wrap('(', self::join($def->arguments, ', '), ')')
                        . ' on ' . self::join($def->locations, ' | ');
                }
            )
        ));

        $types = $this->substituteKeys($types);

        return $types;
    }

    // -.-

    private function substituteKeys($array) {
        $newArray = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {

                if (isset($value[self::NAME_TO_KEY])) {
                    $key = $value[self::NAME_TO_KEY];
                    unset($value[self::NAME_TO_KEY]);
                }

                $substitutedValue = $this->substituteKeys($value);
                $newArray[$key] = $substitutedValue;

            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }
}
