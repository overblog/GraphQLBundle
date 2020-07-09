<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Builder;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Builder\RelayEdgeFieldsBuilder;
use PHPUnit\Framework\TestCase;

class RelayEdgeFieldsBuilderTest extends TestCase
{
    protected function doMapping(array $config): array
    {
        $builder = new RelayEdgeFieldsBuilder();

        return $builder->toMappingDefinition($config);
    }

    public function testMissingNodeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using the Relay Edge fields builder, the key "nodeType" defining the GraphQL type of the node is required and must be a string.');
        $this->doMapping([]);
    }

    public function testInvalidNodeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using the Relay Edge fields builder, the key "nodeType" defining the GraphQL type of the node is required and must be a string.');
        $this->doMapping(['nodeType' => true]);
    }

    public function testValidConfig(): void
    {
        $config = [
            'nodeType' => 'MyNode',
            'nodeDescription' => 'Custom node description',
            'pageInfoType' => 'CustomPageInfo',
            'pageInfoDescription' => 'Custom page info description',
        ];
        $expected = [
            'node' => [
                'description' => $config['nodeDescription'],
                'type' => 'MyNode',
            ],
            'cursor' => [
                'description' => 'The edge cursor',
                'type' => 'String!',
            ],
        ];
        $this->assertSame($this->doMapping($config), $expected);
    }
}
