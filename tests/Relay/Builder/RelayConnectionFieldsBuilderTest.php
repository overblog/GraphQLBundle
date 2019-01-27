<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Builder;

use Overblog\GraphQLBundle\Relay\Builder\RelayConnectionFieldsBuilder;
use PHPUnit\Framework\TestCase;

class RelayConnectionFieldsBuilderTest extends TestCase
{
    protected function doMapping(array $config)
    {
        $builder = new RelayConnectionFieldsBuilder();

        return $builder->toMappingDefinition($config);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Using the Relay Connection fields builder, the key "edgeType" defining the GraphQL type of edges is required and must be a string.
     */
    public function testMissingEdgeType(): void
    {
        $this->doMapping([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Using the Relay Connection fields builder, the key "edgeType" defining the GraphQL type of edges is required and must be a string.
     */
    public function testInvalidEdgeType(): void
    {
        $this->doMapping(['edgeType' => true]);
    }

    public function testValidConfig(): void
    {
        $config = [
            'edgeType' => 'MyEdge',
            'edgeDescription' => 'Custom edge description',
            'pageInfoType' => 'CustomPageInfo',
            'pageInfoDescription' => 'Custom page info description',
        ];
        $expected = [
            'edges' => [
                'description' => $config['edgeDescription'],
                'type' => '[MyEdge]',
            ],
            'pageInfo' => [
                'description' => $config['pageInfoDescription'],
                'type' => 'Custom page info description',
            ],
        ];
        $this->assertSame($this->doMapping($config), $expected);
    }
}
