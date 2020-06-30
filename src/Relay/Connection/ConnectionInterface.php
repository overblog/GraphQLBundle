<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

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
     */
    public function setPageInfo(PageInfoInterface $pageInfo);

    /**
     * Get the total count.
     *
     * @return int
     */
    public function getTotalCount(): ?int;

    /**
     * Set the total count.
     */
    public function setTotalCount(int $totalCount);
}
