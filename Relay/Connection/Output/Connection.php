<?php

namespace Overblog\GraphBundle\Relay\Connection\Output;

final class Connection
{
    /** @var Edge[]  */
    public $edges = [];

    /** @var PageInfo  */
    public $pageInfo;

    public function __construct(array $edges, PageInfo $pageInfo)
    {
        $this->edges = $edges;
        $this->pageInfo = $pageInfo;
    }
}
