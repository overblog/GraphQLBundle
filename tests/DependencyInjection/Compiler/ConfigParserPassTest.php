<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Compiler;

use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ConfigParserPass;
use Overblog\GraphQLBundle\DependencyInjection\OverblogGraphQLExtension;
use Overblog\GraphQLBundle\Error\ExceptionConverter;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\BoxFields;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\MutationField;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\PagerArgs;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\RawIdField;
use Overblog\GraphQLBundle\Tests\DependencyInjection\Builder\TimestampFields;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use function preg_quote;
use function sprintf;
use const DIRECTORY_SEPARATOR;

class ConfigParserPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ConfigParserPass $compilerPass;

    public function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->compilerPass = new ConfigParserPass();
    }

    public function tearDown(): void
    {
        unset($this->container, $this->compilerPass);
    }

    public function testBrokenYmlOnPrepend(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#The file "(.*)'.preg_quote(DIRECTORY_SEPARATOR).'broken.types.yml" does not contain valid YAML\.#');
        $this->processCompilerPass($this->getMappingConfig('yaml'));
    }

    public function testBrokenXmlOnPrepend(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#Unable to parse file "(.*)'.preg_quote(DIRECTORY_SEPARATOR).'broken.types.xml"\.#');
        $this->processCompilerPass($this->getMappingConfig('xml'));
    }

    public function testPreparseOnPrepend(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "overblog_graphql_types.Type._object_config.fields" should have at least 1 element(s) defined.');
        $this->processCompilerPass($this->getMappingConfig('annotation'));
    }

    /**
     * @dataProvider internalConfigKeys
     */
    public function testInternalConfigKeysShouldNotBeUsed(string $internalConfigKey): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Don\'t use internal config keys _object_config, _enum_config, _interface_config, _union_config, _input_object_config, _custom_scalar_config, replace it by "config" instead.');
        $configs = [
            ['bar' => [$internalConfigKey => []]],
        ];

        $this->compilerPass->processConfiguration($configs);
    }

    /**
     * @dataProvider fieldBuilderTypeOverrideNotAllowedProvider
     * @runInSeparateProcess
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

        $this->expectException($exceptionClass); // @phpstan-ignore-line
        $this->expectExceptionMessage($exceptionMessage);

        $this->compilerPass->processConfiguration([$configs]);
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

        $definition = $this->container->getDefinition(ExceptionConverter::class);

        $this->assertSame($expectedExceptionMap, $definition->getArgument(0));
    }

    /**
     * @runInSeparateProcess
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

        $config = $this->compilerPass->processConfiguration(
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
            ]
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
                            ],
                            'updatedAt' => [
                                'description' => 'The update date of the object',
                                'type' => 'Int!',
                                'resolve' => '@=value.updatedAt',
                            ],
                            'rawIDWithDescriptionOverride' => [
                                'description' => 'rawIDWithDescriptionOverride description',
                                'type' => 'Int!',
                                'resolve' => '@=value.id',
                            ],
                            'rawID' => [
                                'description' => 'The raw ID of an object',
                                'type' => 'Int!',
                                'resolve' => '@=value.id',
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
                            'foo' => ['type' => 'FooBox!'],
                            'bar' => ['type' => 'BarBox!'],
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
                            'isEmpty' => ['type' => 'Boolean!'],
                            'item' => ['type' => 'Foo'],
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
                            'isEmpty' => ['type' => 'Boolean!'],
                            'item' => ['type' => 'Bar'],
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
                            'fooString' => ['type' => 'String!'],
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
                            '_error' => ['type' => 'String'],
                            'bar' => ['type' => 'String'],
                        ],
                        'name' => 'FooFailurePayload',
                        'builders' => [],
                        'interfaces' => [],
                    ],
                ],
            ],
            $config
        );
    }

    public function internalConfigKeys(): array
    {
        return [
            ['_object_config'],
            ['_enum_config'],
            ['_interface_config'],
            ['_union_config'],
            ['_input_object_config'],
        ];
    }

    private function getMappingConfig(string $type): array
    {
        return [
            'definitions' => [
                'mappings' => [
                    'types' => [
                        [
                            'types' => [$type],
                            'dir' => __DIR__.'/../mapping/'.$type,
                        ],
                    ],
                ],
            ],
            'doctrine' => ['types_mapping' => []],
        ];
    }

    public function fieldBuilderTypeOverrideNotAllowedProvider(): array
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
                sprintf($expectedMessage, 'FooInput', MutationField::class, 'configs'),
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
                sprintf($expectedMessage, 'FooInput', MutationField::class, MutationField::class),
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
                sprintf($expectedMessage, 'FooBox', BoxFields::class, 'configs'),
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
                sprintf($expectedMessage, 'FooBox', BoxFields::class, BoxFields::class),
            ],
        ];
    }

    private function processCompilerPass(array $configs, ?ConfigParserPass $compilerPass = null, ?ContainerBuilder $container = null): void
    {
        $container = $container ?? $this->container;
        $compilerPass = $compilerPass ?? $this->compilerPass;
        $container->setParameter('overblog_graphql.config', $configs);
        $compilerPass->process($container);
    }
}
