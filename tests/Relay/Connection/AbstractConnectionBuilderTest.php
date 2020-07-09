<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;
use PHPUnit\Framework\TestCase;
use function array_flip;
use function array_intersect_key;
use function array_values;
use function count;
use function end;

abstract class AbstractConnectionBuilderTest extends TestCase
{
    protected array $letters = ['A', 'B', 'C', 'D', 'E'];

    protected function getExpectedConnection(array $wantedEdges, bool $hasPreviousPage, bool $hasNextPage): ConnectionInterface
    {
        $edges = [
            'A' => new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
            'B' => new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
            'C' => new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            'D' => new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            'E' => new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
        ];

        $expectedEdges = array_values(array_intersect_key($edges, array_flip($wantedEdges)));

        return new Connection(
            $expectedEdges,
            new PageInfo(
                isset($expectedEdges[0]) ? $expectedEdges[0]->getCursor() : null,
                end($expectedEdges) ? end($expectedEdges)->getCursor() : null,
                $hasPreviousPage,
                $hasNextPage
            )
        );
    }

    protected function assertSameConnection(ConnectionInterface $expectedConnection, ?ConnectionInterface $actualConnection): void
    {
        // assert totalCount
        $this->assertSame($expectedConnection->getTotalCount(), $actualConnection->getTotalCount());

        // assert pageInfo
        foreach (['getEndCursor', 'getHasNextPage', 'getHasPreviousPage', 'getStartCursor'] as $method) {
            $this->assertSame(
                $expectedConnection->getPageInfo()->$method(),
                $actualConnection->getPageInfo()->$method()
            );
        }

        // assert edges
        $this->assertCount(count($expectedConnection->getEdges()), $actualConnection->getEdges());
        foreach ($expectedConnection->getEdges() as $i => $expectedEdge) {
            $this->assertSame($expectedEdge->getNode(), $actualConnection->getEdges()[$i]->getNode());
            $this->assertSame($expectedEdge->getCursor(), $actualConnection->getEdges()[$i]->getCursor());
        }
    }
}
