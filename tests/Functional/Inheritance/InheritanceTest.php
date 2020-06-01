<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Inheritance;

use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class InheritanceTest extends TestCase
{
    /** @var array */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'inheritance']);
        $this->config = (array) static::$kernel->getContainer()->getParameter('overblog_graphql_types.config');
    }

    public function testObjectInheritance(): void
    {
        $this->assertArrayHasKey('Query', $this->config);
        // TODO(mcg-web): understand why travis fields order diffed from local test
        $this->assertEquals(
            [
                'type' => 'object',
                InheritanceProcessor::INHERITS_KEY => ['QueryFoo', 'QueryBar', 'QueryHelloWord'],
                'class_name' => 'QueryType',
                'decorator' => false,
                'config' => [
                    'fields' => [
                        'sayHello' => [
                            'type' => 'String',
                        ],
                        'period' => [
                            'type' => 'Period',
                        ],
                        'bar' => [
                            'type' => 'String',
                        ],
                    ],
                    'name' => 'Query',
                    'interfaces' => ['QueryHelloWord'],
                    'builders' => [],
                ],
            ],
            $this->config['Query']
        );
    }

    public function testEnumInheritance(): void
    {
        $this->assertArrayHasKey('Period', $this->config);
        $this->assertSame(
            [
                'type' => 'enum',
                InheritanceProcessor::INHERITS_KEY => ['Day', 'Month', 'Year'],
                'class_name' => 'PeriodType',
                'decorator' => false,
                'config' => [
                    'values' => [
                        'DAY' => ['value' => 1],
                        'MONTH' => ['value' => 2],
                        'YEAR' => ['value' => 3],
                    ],
                    'name' => 'Period',
                ],
            ],
            $this->config['Period']
        );
    }

    public function testRelayInheritance(): void
    {
        $this->assertArrayHasKey('ChangeEventInput', $this->config);
        $this->assertSame(
            [
                'type' => 'input-object',
                InheritanceProcessor::INHERITS_KEY => ['AddEventInput'],
                'class_name' => 'ChangeEventInputType',
                'decorator' => false,
                'config' => [
                    'name' => 'ChangeEventInput',
                    'fields' => [
                        'title' => ['type' => 'String!'],
                        'clientMutationId' => ['type' => 'String'],
                        'id' => ['type' => 'ID!'],
                    ],
                ],
            ],
            $this->config['ChangeEventInput']
        );
    }

    public function testDecoratorTypeShouldRemovedFromFinalConfig(): void
    {
        $this->assertArrayNotHasKey('QueryBarDecorator', $this->config);
        $this->assertArrayNotHasKey('QueryFooDecorator', $this->config);
    }

    public function testDecoratorInterfacesShouldMerge(): void
    {
        $this->assertArrayHasKey('ABCDE', $this->config);
        $this->assertSame(
            [
                'type' => 'object',
                InheritanceProcessor::INHERITS_KEY => ['DecoratorA', 'DecoratorB', 'DecoratorD'],
                'class_name' => 'ABCDEType',
                'decorator' => false,
                'config' => [
                    'interfaces' => ['A', 'AA', 'B', 'C', 'D', 'E'],
                    'fields' => [
                        'a' => [
                            'type' => 'String',
                        ],
                        'aa' => [
                            'type' => 'String',
                        ],
                        'b' => [
                            'type' => 'String',
                        ],
                        'c' => [
                            'type' => 'String',
                        ],
                        'd' => [
                            'type' => 'String',
                        ],
                        'e' => [
                            'type' => 'String',
                        ],
                    ],
                    'name' => 'ABCDE',
                    'builders' => [],
                ],
            ],
            $this->config['ABCDE']
        );
    }
}
