<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;

class Paginator
{
    /**
     * @var callable
     */
    private $fetcher;

    /**
     * @param callable $fetcher
     */
    public function __construct(callable $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * @param Argument|array $args
     * @param int|callable   $total
     *
     * @return Connection
     */
    public function backward($args, $total)
    {
        $args = $this->protectArgs($args);
        $limit = $args['last'];
        $offset = max(0, ConnectionBuilder::getOffsetWithDefault($args['before'], $total) - $limit);

        $entities = call_user_func($this->fetcher, $offset, $limit);

        return ConnectionBuilder::connectionFromArraySlice($entities, $args, [
            'sliceStart' => $offset,
            'arrayLength' => $total,
        ]);
    }

    /**
     * @param Argument|array $args
     *
     * @return Connection
     */
    public function forward($args)
    {
        $args = $this->protectArgs($args);
        $limit = $args['first'];
        $offset = ConnectionBuilder::getOffsetWithDefault($args['after'], 0);

        // If we don't have a cursor or if it's not valid, then we must not use the slice method
        if (!is_numeric(ConnectionBuilder::cursorToOffset($args['after'])) || !$args['after']) {
            $entities = call_user_func($this->fetcher, $offset, $limit + 1);

            return ConnectionBuilder::connectionFromArray($entities, $args);
        } else {
            $entities = call_user_func($this->fetcher, $offset, $limit + 2);

            return ConnectionBuilder::connectionFromArraySlice($entities, $args, [
                'sliceStart' => $offset,
                'arrayLength' => $offset + count($entities),
            ]);
        }
    }

    /**
     * @param Argument|array $args
     * @param int|callable   $total
     *
     * @return Connection
     */
    public function auto($args, $total)
    {
        $args = $this->protectArgs($args);

        if ($args['last']) {
            return $this->backward($args, is_callable($total) ? call_user_func($total) : $total);
        } else {
            return $this->forward($args);
        }
    }

    /**
     * @param Argument|array $args
     *
     * @return Argument
     */
    private function protectArgs($args)
    {
        return $args instanceof Argument ? $args : new Argument($args);
    }
}
