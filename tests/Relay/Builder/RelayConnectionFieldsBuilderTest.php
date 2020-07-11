<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Builder;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Builder\RelayConnectionFieldsBuilder;
use PHPUnit\Framework\TestCase;

class RelayConnectionFieldsBuilderTest extends TestCase
{
    protected function doMapping(array $config): array
    {
        $builder = new RelayConnectionFieldsBuilder();

        return $builder->toMappingDefinition($config);
    }

    public function testMissingEdgeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using the Relay Connection fields builder, the key "edgeType" defining the GraphQL type of edges is required and must be a string.');
        $this->doMapping([]);
    }

    public function testInvalidEdgeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using the Relay Connection fields builder, the key "edgeType" defining the GraphQL type of edges is required and must be a string.');
        $this->doMapping(['edgeType' => true]);
    }

    public function testValidConfig(): void
    {
        $config = [
            'edgeType' => 'MyEdge',
            'edgeDescription' => 'Custom edge description',
            'pageInfoType' => 'CustomPageInfo',
            'pageInfoDescription' => 'Custom page info description',
            'totalCountDescription' => 'Custom total count description',
        ];
        $expected = [
            'edges' => [
                'description' => $config['edgeDescription'],
                'type' => '[MyEdge]',
            ],
            'pageInfo' => [
                'description' => $config['pageInfoDescription'],
                'type' => 'CustomPageInfo',
            ],
            'totalCount' => [
                'description' => $config['totalCountDescription'],
                'type' => 'Int',
            ],
        ];
        $this->assertSame($this->doMapping($config), $expected);
    }
}
