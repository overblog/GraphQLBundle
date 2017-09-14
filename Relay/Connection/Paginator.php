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
    const MODE_REGULAR = false;
    const MODE_PROMISE = true;

    /**
     * @var callable
     */
    private $fetcher;

    /**
     * @var bool
     */
    private $promise;

    /**
     * @var int
     */
    private $totalCount;

    /**
     * @param callable $fetcher
     * @param bool     $promise
     */
    public function __construct(callable $fetcher, $promise = self::MODE_REGULAR)
    {
        $this->fetcher = $fetcher;
        $this->promise = $promise;
    }

    /**
     * @param Argument|array $args
     * @param int|callable   $total
     * @param array          $callableArgs
     *
     * @return Connection
     */
    public function backward($args, $total, array $callableArgs = [])
    {
        $total = $this->computeTotalCount($total, $callableArgs);

        $args = $this->protectArgs($args);
        $limit = $args['last'];
        $offset = max(0, ConnectionBuilder::getOffsetWithDefault($args['before'], $total) - $limit);

        $entities = call_user_func($this->fetcher, $offset, $limit);

        return $this->handleEntities($entities, function ($entities) use ($args, $offset, $total) {
            return ConnectionBuilder::connectionFromArraySlice($entities, $args, [
                'sliceStart' => $offset,
                'arrayLength' => $total,
            ]);
        });
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

            return $this->handleEntities($entities, function ($entities) use ($args) {
                return ConnectionBuilder::connectionFromArray($entities, $args);
            });
        } else {
            $entities = call_user_func($this->fetcher, $offset, $limit + 2);

            return $this->handleEntities($entities, function ($entities) use ($args, $offset) {
                return ConnectionBuilder::connectionFromArraySlice($entities, $args, [
                    'sliceStart' => $offset,
                    'arrayLength' => $offset + count($entities),
                ]);
            });
        }
    }

    /**
     * @param Argument|array $args
     * @param int|callable   $total
     * @param array          $callableArgs
     *
     * @return Connection
     */
    public function auto($args, $total, $callableArgs = [])
    {
        $args = $this->protectArgs($args);

        if ($args['last']) {
            $connection = $this->backward($args, $total, $callableArgs);
        } else {
            $connection = $this->forward($args);
        }

        if ($this->promise) {
            $connection->then(function (Connection $connection) use ($total, $callableArgs) {
                $connection->totalCount = $this->computeTotalCount($total, $callableArgs);
            });
        } else {
            $connection->totalCount = $this->computeTotalCount($total, $callableArgs);
        }

        return $connection;
    }

    /**
     * @param array|object $entities An array of entities to paginate or a promise
     * @param callable     $callback
     *
     * @return Connection|object A connection or a promise
     */
    private function handleEntities($entities, callable $callback)
    {
        if ($this->promise) {
            return $entities->then($callback);
        }

        return call_user_func($callback, $entities);
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

    /**
     * @param int   $total
     * @param array $callableArgs
     *
     * @return int|mixed
     */
    private function computeTotalCount($total, array $callableArgs = [])
    {
        if ($this->totalCount !== null) {
            return $this->totalCount;
        }

        $this->totalCount = is_callable($total) ? call_user_func_array($total, $callableArgs) : $total;

        return $this->totalCount;
    }
}
