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

class EdgeBuilder
{
    /**
     * @param int   $offset
     * @param mixed $node
     *
     * @return Edge
     */
    public static function create($offset, $node)
    {
        return new Edge(ConnectionBuilder::offsetToCursor($offset), $node);
    }

    /**
     * @param array $slice
     * @param int   $startOffset
     *
     * @return Edge[]
     */
    public static function createCollection($slice, $startOffset = 0)
    {
        $edges = [];

        foreach ($slice as $index => $value) {
            $edges[] = static::create($startOffset + $index, $value);
        }

        return $edges;
    }
}
