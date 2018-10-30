<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var Edge[] */
    protected $edges = [];

    /** @var PageInfo */
    protected $pageInfo = null;

    /** @var int */
    protected $totalCount = 0;

    public function __construct($edges = [], PageInfo $pageInfo = null)
    {
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getEdges()
    {
        return $this->edges;
    }

    /**
     * {@inheritdoc}
     */
    public function setEdges(iterable $edges): void
    {
        $this->edges = $edges;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageInfo(): ? PageInfo
    {
        return $this->pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageInfo(PageInfo $pageInfo): void
    {
        $this->pageInfo = $pageInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }
}
