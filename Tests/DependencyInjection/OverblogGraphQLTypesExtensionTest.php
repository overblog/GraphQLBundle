<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\DependencyInjection;

use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLTypesExtension;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\PagerArgs;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\RawIdField;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class OverblogGraphQLTypesExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var OverblogGraphQLTypesExtension
     */
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
     * @expectedException \Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException
     */
    public function testDuplicatedType()
    {
        $type = ['foo' => []];
        $configs = [$type, $type];
        $this->extension->load($configs, $this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #The file "(.*)/broken.types.yml" does not contain valid YAML\.#
     */
    public function testBrokenYmlOnPrepend()
    {
        $this->extension->containerPrependExtensionConfig($this->getBrokenMappingConfig('yaml'), $this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp #Unable to parse file "(.*)/broken.types.xml"\.#
     */
    public function testBrokenXmlOnPrepend()
    {
        $this->extension->containerPrependExtensionConfig($this->getBrokenMappingConfig('xml'), $this->container);
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
                    'definitions' => [
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
        $this->assertEquals($expectedExceptionMap, $definition->getArgument(2));
    }

    /**
     * @runInSeparateProcess
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

        $this->assertEquals(
            [
                'foo' => [
                    'type' => 'object',
                    'config' => [
                        'name' => 'foo',
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

    private function getBrokenMappingConfig($type)
    {
        $config = [
            'definitions' => [
                'mappings' => [
                    'types' => [
                        [
                            'type' => $type,
                            'dir' => __DIR__.'/mapping/'.$type,
                        ],
                    ],
                ],
            ],
        ];

        return $config;
    }
}
