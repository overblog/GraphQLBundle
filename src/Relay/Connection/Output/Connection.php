<?php

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

final class Connection
{
    /** @var Edge[] */
    public $edges = [];

    /** @var PageInfo */
    public $pageInfo;

    /** @var int */
    public $totalCount;

    public function __construct(array $edges, PageInfo $pageInfo)
    {
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
    }
}
