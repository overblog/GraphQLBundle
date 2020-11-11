<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

interface ConnectionInterface
{
    /**
     * Get the connection edges.
     *
     * @return iterable|EdgeInterface[]
     */
    public function getEdges();

    /**
     * Set the connection edges.
     *
     * @param iterable|EdgeInterface[] $edges
     */
    public function setEdges(iterable $edges);

    /**
     * Get the page info.
     *
     * @return PageInfoInterface
     */
    public function getPageInfo(): ?PageInfoInterface;

    /**
     * Set the page info.
     *
     * @param PageInfoInterface $pageInfo
     */
    public function setPageInfo(PageInfoInterface $pageInfo);

    /**
     * Get the total count or promise that returns the total count.
     *
     * @return int|Promise
     */
    public function getTotalCount();

    /**
     * Set the total count or promise that returns the total count.
     *
     * @param int|Promise $totalCount
     */
    public function setTotalCount($totalCount);
}
