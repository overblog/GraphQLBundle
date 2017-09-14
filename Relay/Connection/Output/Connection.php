<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
