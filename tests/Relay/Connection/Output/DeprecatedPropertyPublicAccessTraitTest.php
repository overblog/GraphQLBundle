<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;
use PHPUnit\Framework\TestCase;

class DeprecatedPropertyPublicAccessTraitTest extends TestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Connection::$edges value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Connection::getEdges.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Connection::$pageInfo value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Connection::getPageInfo.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Edge::$cursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Edge::getCursor.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Edge::$node value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Edge::getNode.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$startCursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::getStartCursor.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$endCursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::getEndCursor.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$hasPreviousPage value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::getHasPreviousPage.
     * @expectedDeprecation Getting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$hasNextPage value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::getHasNextPage.
     */
    public function testProtectedPropertyReadAccess(): void
    {
        $connection = new Connection(
            [new Edge('foo', 'bar')],
            new PageInfo('foo', 'baz', true, false)
        );

        $this->assertSame($connection->getEdges(), $connection->edges);
        $this->assertSame($connection->getPageInfo(), $connection->pageInfo);
        $this->assertSame($connection->getEdges()[0]->getCursor(), $connection->getEdges()[0]->cursor);
        $this->assertSame($connection->getEdges()[0]->getNode(), $connection->getEdges()[0]->node);
        $this->assertSame($connection->getPageInfo()->getStartCursor(), $connection->getPageInfo()->startCursor);
        $this->assertSame($connection->getPageInfo()->getEndCursor(), $connection->getPageInfo()->endCursor);
        $this->assertSame($connection->getPageInfo()->getHasPreviousPage(), $connection->getPageInfo()->hasPreviousPage);
        $this->assertSame($connection->getPageInfo()->getHasNextPage(), $connection->getPageInfo()->hasNextPage);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Connection::$edges value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Connection::setEdges.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Connection::$pageInfo value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Connection::setPageInfo.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Edge::$cursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Edge::setCursor.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\Edge::$node value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\Edge::setNode.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$startCursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::setStartCursor.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$endCursor value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::setEndCursor.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$hasPreviousPage value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::setHasPreviousPage.
     * @expectedDeprecation Setting directly property Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::$hasNextPage value is deprecated as of 0.12 and will be removed in 0.13. You should now use method Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo::setHasNextPage.
     */
    public function testProtectedPropertyWriteAccess(): void
    {
        $connection = new Connection();
        $edges = [$edge = new Edge()];
        $pageInfo = new PageInfo();

        $connection->edges = $edges;
        $this->assertSame($edges, $connection->getEdges());
        $connection->pageInfo = $pageInfo;
        $this->assertSame($pageInfo, $connection->getPageInfo());
        $edge->cursor = 'cursor';
        $this->assertSame('cursor', $edge->getCursor());
        $edge->node = 'node';
        $this->assertSame('node', $edge->getNode());
        $pageInfo->startCursor = 'startCursor';
        $this->assertSame('startCursor', $pageInfo->getStartCursor());
        $pageInfo->endCursor = 'endCursor';
        $this->assertSame('endCursor', $pageInfo->getEndCursor());
        $pageInfo->hasPreviousPage = true;
        $this->assertTrue($pageInfo->getHasPreviousPage());
        $pageInfo->hasNextPage = false;
        $this->assertFalse($pageInfo->getHasNextPage());
    }

    public function testAllowExtraProperties(): void
    {
        $connection = new Connection();
        $connection->extra = 'value';

        $this->assertSame('value', $connection->extra);
    }
}
