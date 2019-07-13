<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection;

use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLTypesExtension;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\PagerArgs;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\RawIdField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class OverblogGraphQLTypesExtensionTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var OverblogGraphQLTypesExtension */
    private $extension;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->extension = new OverblogGraphQLTypesExtension();
    }

    public function tearDown()
    {
        unset($this->container, $this->extension);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configs type should never contain more than one config to deal with inheritance.
     */
    public function testMultipleConfigNotAllowed()
    {
        $configs = [['foo' => []], ['bar' => []]];
        $this->extension->load($configs, $this->container);
    }

    public function testBrokenYmlOnPrepend()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#The file "(.*)'.\preg_quote(\DIRECTORY_SEPARATOR).'broken.types.yml" does not contain valid YAML\.#');
        $this->extension->containerPrependExtensionConfig($this->getMappingConfig('yaml'), $this->container);
    }

    public function testBrokenXmlOnPrepend()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#Unable to parse file "(.*)'.\preg_quote(\DIRECTORY_SEPARATOR).'broken.types.xml"\.#');
        $this->extension->containerPrependExtensionConfig($this->getMappingConfig('xml'), $this->container);
    }

    /**
     * @param $internalConfigKey
     * @dataProvider internalConfigKeys
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Don't use internal config keys _object_config, _enum_config, _interface_config, _union_config, _input_object_config, _custom_scalar_config, replace it by "config" instead.
     */
    public function testInternalConfigKeysShouldNotBeUsed($internalConfigKey)
    {
        $configs = [
            ['bar' => [$internalConfigKey => []]],
        ];

        $this->extension->load($configs, $this->container);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCustomExceptions()
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
    public function testCustomBuilders()
    {
        $ext = new OverblogGraphQLExtension();
        $ext->load(
            [
                [
                    'definitions' => [
                        'builders' => [
                            'field' => [
                                'RawId' => RawIdField::class,
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
                            'fields' => [
                                'rawIDWithDescriptionOverride' => [
                                    'builder' => 'RawId',
                                    'description' => 'rawIDWithDescriptionOverride description',
                                ],
                                'rawID' => 'RawId',
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
        ];

        return $config;
    }
}
