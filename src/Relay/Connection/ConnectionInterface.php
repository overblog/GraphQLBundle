<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

interface ConnectionInterface
{
    /**
     * Get the connection edges.
     *
     * @return iterable
     */
    public function getEdges();

    /**
     * Set the connection edges.
     *
     * @param iterable $edges
     */
    public function setEdges(iterable $edges);

    /**
     * Get the page info.
     *
     * @return PageInfo
     */
    public function getPageInfo(): ? PageInfo;

    /**
     * Set the page info.
     *
     * @param PageInfo $pageInfo
     */
    public function setPageInfo(PageInfo $pageInfo);

    /**
     * Get the total count.
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Set the total count.
     *
     * @param int $totalCount
     */
    public function setTotalCount(int $totalCount);
}
