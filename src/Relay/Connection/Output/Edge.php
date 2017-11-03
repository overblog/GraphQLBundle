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

final class Edge
{
    /** @var string */
    public $cursor;

    /** @var mixed */
    public $node;

    public function __construct($cursor, $node)
    {
        $this->cursor = $cursor;
        $this->node = $node;
    }
}
