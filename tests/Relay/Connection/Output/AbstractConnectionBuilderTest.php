<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;
use PHPUnit\Framework\TestCase;

abstract class AbstractConnectionBuilderTest extends TestCase
{
    protected $letters = ['A', 'B', 'C', 'D', 'E'];

    protected function getExpectedConnection(array $wantedEdges, $hasPreviousPage, $hasNextPage)
    {
        $edges = [
            'A' => new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
            'B' => new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
            'C' => new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            'D' => new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            'E' => new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
        ];

        $expectedEdges = \array_values(\array_intersect_key($edges, \array_flip($wantedEdges)));

        return new Connection(
            $expectedEdges,
            new PageInfo(
                isset($expectedEdges[0]) ? $expectedEdges[0]->cursor : null,
                \end($expectedEdges) ? \end($expectedEdges)->cursor : null,
                $hasPreviousPage,
                $hasNextPage
            )
        );
    }

    protected function assertSameConnection(Connection $expectedConnection, Connection $actualConnection): void
    {
        // assert totalCount
        $this->assertSame($expectedConnection->totalCount, $actualConnection->totalCount);

        // assert pageInfo
        foreach (['endCursor', 'hasNextPage', 'hasPreviousPage', 'startCursor'] as $property) {
            $this->assertSame(
                $expectedConnection->pageInfo->$property,
                $actualConnection->pageInfo->$property
            );
        }

        // assert edges
        $this->assertCount(\count($expectedConnection->edges), $actualConnection->edges);
        foreach ($expectedConnection->edges as $i => $expectedEdge) {
            foreach (['cursor', 'node'] as $property) {
                $this->assertSame($expectedEdge->$property, $actualConnection->edges[$i]->$property);
            }
        }
    }
}
