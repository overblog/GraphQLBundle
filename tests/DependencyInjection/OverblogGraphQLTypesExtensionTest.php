<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection;

use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLTypesExtension;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\BoxFields;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\MutationField;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\PagerArgs;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\RawIdField;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\TimestampFields;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class OverblogGraphQLTypesExtensionTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var OverblogGraphQLTypesExtension */
    private $extension;

    public function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->extension = new OverblogGraphQLTypesExtension();
    }

    public function tearDown(): void
    {
        unset($this->container, $this->extension);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configs type should never contain more than one config to deal with inheritance.
     */
    public function testMultipleConfigNotAllowed(): void
    {
        $configs = [['foo' => []], ['bar' => []]];
        $this->extension->load($configs, $this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #The file "(.*)/broken.types.yml" does not contain valid YAML\.#
     */
    public function testBrokenYmlOnPrepend(): void
    {
        $this->extension->containerPrependExtensionConfig($this->getMappingConfig('yaml'), $this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #Unable to parse file "(.*)/broken.types.xml"\.#
     */
    public function testBrokenXmlOnPrepend(): void
    {
        $this->extension->containerPrependExtensionConfig($this->getMappingConfig('xml'), $this->container);
    }

    public function testPreparseOnPrepend(): void
    {
        $this->extension->containerPrependExtensionConfig($this->getMappingConfig('annotation'), $this->container);
        $expected = [0 => [
            'Type' => [
                'type' => 'object',
                'config' => ['fields' => []],
            ],
        ]];

        $this->assertEquals($this->container->getExtensionConfig('overblog_graphql_types'), $expected);
    }

    /**
     * @param $internalConfigKey
     * @dataProvider internalConfigKeys
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Don't use internal config keys _object_config, _enum_config, _interface_config, _union_config, _input_object_config, _custom_scalar_config, replace it by "config" instead.
     */
    public function testInternalConfigKeysShouldNotBeUsed($internalConfigKey): void
    {
        $configs = [
            ['bar' => [$internalConfigKey => []]],
        ];

        $this->extension->load($configs, $this->container);
    }

    /**
     * @dataProvider fieldBuilderTypeOverrideNotAllowedProvider
     * @runInSeparateProcess
     *
     * @param array  $builders
     * @param array  $configs
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    public function testFieldBuilderTypeOverrideNotAllowed(array $builders, array $configs, string $exceptionClass, string $exceptionMessage): void
    {
        $ext = new OverblogGraphQLExtension();
        $ext->load(
            [
                ['definitions' => ['builders' => $builders]],
            ],
            $this->container
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        $this->extension->load([$configs], $this->container);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCustomExceptions(): void
    {
        $ext = new OverblogGraphQLExtension();
        $ext->load(
            [
                [
                    'errors_handler' => [
                        'exceptions' => [
                            'warnings' => [
                                ResourceNotFoundException::class,
                            ],
                            'errors' => [
                                \InvalidArgumentException::class,
                            ],
                        ],
                    ],
                ],
            ],
            $this->container
        );

        $expectedExceptionMap = [
            ResourceNotFoundException::class => UserWarning::class,
            \InvalidArgumentException::class => UserError::class,
        ];

        $definition = $this->container->getDefinition('overblog_graphql.error_handler');
        $this->assertSame($expectedExceptionMap, $definition->getArgument(2));
    }

    /**
     * @runInSeparateProcess
     * @group legacy
     */
    public function testCustomBuilders(): void
    {
        $ext = new OverblogGraphQLExtension();
        $ext->load(
            [
                [
                    'definitions' => [
                        'builders' => [
                            'field' => [
                                'RawId' => RawIdField::class,
                                'Mutation' => MutationField::class,
                            ],
                            'fields' => [
                                'Timestamps' => TimestampFields::class,
                                'Boxes' => BoxFields::class,
                            ],
                            'args' => [
                                [
                                    'alias' => 'Pager',
                                    'class' => PagerArgs::class,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $this->container
        );

        $this->extension->load(
            [
                [
                    'foo' => [
                        'type' => 'object',
                        'config' => [
                            'builders' => [
                                [
                                    'builder' => 'Timestamps',
                                    'builderConfig' => ['param1' => 'val1'],
                                ],
                            ],
                            'fields' => [
                                'rawIDWithDescriptionOverride' => [
                                    'builder' => 'RawId',
                                    'description' => 'rawIDWithDescriptionOverride description',
                                ],
                                'rawID' => ['builder' => 'RawId'],
                                'rawIDs' => [
                                    'type' => '[RawID!]!',
                                    'argsBuilder' => 'Pager',
                                ],
                                'categories' => [
                                    'type' => '[String!]!',
                                    'argsBuilder' => ['builder' => 'Pager'],
                                ],
                                'categories2' => [
                                    'type' => '[String!]!',
                                    'argsBuilder' => ['builder' => 'Pager', 'config' => ['defaultLimit' => 50]],
                                ],
                            ],
                        ],
                    ],
                    'Boxes' => [
                        'type' => 'object',
                        'config' => [
                            'builders' => [
                                [
                                    'builder' => 'Boxes',
                                    'builderConfig' => [
                                        'foo' => 'Foo',
                                        'bar' => 'Bar',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'Mutation' => [
                        'type' => 'object',
                        'config' => [
                            'fields' => [
                                'foo' => [
                                    'builder' => 'Mutation',
                                    'builderConfig' => [
                                        'name' => 'Foo',
                                        'resolver' => 'Mutation.foo',
                                        'inputFields' => [
                                            'bar' => ['type' => 'String!'],
                                        ],
                                        'payloadFields' => [
                                            'fooString' => ['type' => 'String!'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $this->container
        );

        $this->assertSame(
            [
                'foo' => [
                    'type' => 'object',
                    'class_name' => 'fooType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'createdAt' => [
                                'description' => 'The creation date of the object',
                                'type' => 'Int!',
                                'resolve' => '@=value.createdAt',
                                'args' => [],
                            ],
                            'updatedAt' => [
                                'description' => 'The update date of the object',
                                'type' => 'Int!',
                                'resolve' => '@=value.updatedAt',
                                'args' => [],
                            ],
                            'rawIDWithDescriptionOverride' => [
                                'description' => 'rawIDWithDescriptionOverride description',
                                'type' => 'Int!',
                                'resolve' => '@=value.id',
                                'args' => [],
                            ],
                            'rawID' => [
                                'description' => 'The raw ID of an object',
                                'type' => 'Int!',
                                'resolve' => '@=value.id',
                                'args' => [],
                            ],
                            'rawIDs' => [
                                'type' => '[RawID!]!',
                                'args' => [
                                    'limit' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 20,
                                    ],
                                    'offset' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 0,
                                    ],
                                ],
                            ],
                            'categories' => [
                                'type' => '[String!]!',
                                'args' => [
                                    'limit' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 20,
                                    ],
                                    'offset' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 0,
                                    ],
                                ],
                            ],
                            'categories2' => [
                                'type' => '[String!]!',
                                'args' => [
                                    'limit' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 50,
                                    ],
                                    'offset' => [
                                        'type' => 'Int!',
                                        'defaultValue' => 0,
                                    ],
                                 ],
                            ],
                        ],
                        'name' => 'foo',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'Boxes' => [
                    'type' => 'object',
                    'class_name' => 'BoxesType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'foo' => ['type' => 'FooBox!', 'args' => []],
                            'bar' => ['type' => 'BarBox!', 'args' => []],
                        ],
                        'name' => 'Boxes',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'Mutation' => [
                    'type' => 'object',
                    'class_name' => 'MutationType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'foo' => [
                                'type' => 'FooPayload!',
                                'resolve' => '@=mutation("Mutation.foo", [args["input"]])',
                                'args' => [
                                    'input' => ['type' => 'FooInput!'],
                                ],
                            ],
                        ],
                        'name' => 'Mutation',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'FooBox' => [
                    'type' => 'object',
                    'class_name' => 'FooBoxType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'isEmpty' => ['type' => 'Boolean!', 'args' => []],
                            'item' => ['type' => 'Foo', 'args' => []],
                        ],
                        'name' => 'FooBox',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'BarBox' => [
                    'type' => 'object',
                    'class_name' => 'BarBoxType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'isEmpty' => ['type' => 'Boolean!', 'args' => []],
                            'item' => ['type' => 'Bar', 'args' => []],
                        ],
                        'name' => 'BarBox',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'FooInput' => [
                    'type' => 'input-object',
                    'class_name' => 'FooInputType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'bar' => ['type' => 'String!'],
                        ],
                        'name' => 'FooInput',
                    ],
                ],
                'FooPayload' => [
                    'type' => 'union',
                    'class_name' => 'FooPayloadType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'types' => ['FooSuccessPayload', 'FooFailurePayload'],
                        'resolveType' => '@=resolver("PayloadTypeResolver", [value, "FooSuccessPayload", "FooFailurePayload"])',
                        'name' => 'FooPayload',
                    ],
                ],
                'FooSuccessPayload' => [
                    'type' => 'object',
                    'class_name' => 'FooSuccessPayloadType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            'fooString' => ['type' => 'String!', 'args' => []],
                        ],
                        'name' => 'FooSuccessPayload',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
                'FooFailurePayload' => [
                    'type' => 'object',
                    'class_name' => 'FooFailurePayloadType',
                    InheritanceProcessor::INHERITS_KEY => [],
                    'decorator' => false,
                    'config' => [
                        'fields' => [
                            '_error' => ['type' => 'String', 'args' => []],
                            'bar' => ['type' => 'String', 'args' => []],
                        ],
                        'name' => 'FooFailurePayload',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
            ],
            $this->container->getParameter('overblog_graphql_types.config')
        );
    }

    public function internalConfigKeys()
    {
        return [
            ['_object_config'],
            ['_enum_config'],
            ['_interface_config'],
            ['_union_config'],
            ['_input_object_config'],
        ];
    }

    private function getMappingConfig($type)
    {
        $config = [
            'definitions' => [
                'mappings' => [
                    'types' => [
                        [
                            'types' => [$type],
                            'dir' => __DIR__.'/mapping/'.$type,
                        ],
                    ],
                ],
            ],
            'doctrine' => ['types_mapping' => []],
        ];

        return $config;
    }

    public function fieldBuilderTypeOverrideNotAllowedProvider()
    {
        $expectedMessage = 'Type "%s" emitted by builder "%s" already exists. Type was provided by "%s". Builder may only emit new types. Overriding is not allowed.';

        $simpleObjectType = [
            'type' => 'object',
            'config' => [
                'fields' => [
                    'value' => ['type' => 'String'],
                ],
            ],
        ];

        $mutationFieldBuilder = [
            'builder' => 'Mutation',
            'builderConfig' => [
                'name' => 'Foo',
                'resolver' => 'Mutation.foo',
                'inputFields' => [
                    'bar' => ['type' => 'String!'],
                ],
                'payloadFields' => [
                    'fooString' => ['type' => 'String!'],
                ],
            ],
        ];

        $boxFieldsBuilders = [
            [
                'builder' => 'Boxes',
                'builderConfig' => [
                    'foo' => 'Foo',
                    'bar' => 'Bar',
                ],
            ],
        ];

        return [
            [
                ['field' => ['Mutation' => MutationField::class]],
                [
                    'Mutation' => [
                        'type' => 'object',
                        'config' => [
                            'fields' => [
                                'foo' => $mutationFieldBuilder,
                            ],
                        ],
                    ],
                    'FooInput' => $simpleObjectType,
                ],
                InvalidConfigurationException::class,
                \sprintf($expectedMessage, 'FooInput', MutationField::class, 'configs'),
            ],
            [
                ['field' => ['Mutation' => MutationField::class]],
                [
                    'Mutation' => [
                        'type' => 'object',
                        'config' => [
                            'fields' => [
                                'foo' => $mutationFieldBuilder,
                                'bar' => $mutationFieldBuilder,
                            ],
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                \sprintf($expectedMessage, 'FooInput', MutationField::class, MutationField::class),
            ],
            [
                ['fields' => ['Boxes' => BoxFields::class]],
                [
                    'Boxes' => [
                        'type' => 'object',
                        'config' => [
                            'builders' => $boxFieldsBuilders,
                        ],
                    ],
                    'FooBox' => $simpleObjectType,
                ],
                InvalidConfigurationException::class,
                \sprintf($expectedMessage, 'FooBox', BoxFields::class, 'configs'),
            ],
            [
                ['fields' => ['Boxes' => BoxFields::class]],
                [
                    'Boxes' => [
                        'type' => 'object',
                        'config' => [
                            'builders' => $boxFieldsBuilders,
                        ],
                    ],
                    'OtherBoxes' => [
                        'type' => 'object',
                        'config' => [
                            'builders' => $boxFieldsBuilders,
                        ],
                    ],
                ],
                InvalidConfigurationException::class,
                \sprintf($expectedMessage, 'FooBox', BoxFields::class, BoxFields::class),
            ],
        ];
    }
}
