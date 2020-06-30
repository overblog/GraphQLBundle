<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use function call_user_func;
use function call_user_func_array;
use function count;
use function is_callable;
use function is_numeric;
use function max;

class Paginator
{
    public const MODE_REGULAR = false;
    public const MODE_PROMISE = true;

    private bool $promise;
    private int $totalCount;
    private ConnectionBuilder $connectionBuilder;

    /** @var callable */
    private $fetcher;

    public function __construct(callable $fetcher, bool $promise = self::MODE_REGULAR, ConnectionBuilder $connectionBuilder = null)
    {
        $this->fetcher = $fetcher;
        $this->promise = $promise;
        $this->connectionBuilder = $connectionBuilder ?? new ConnectionBuilder();
    }

    /**
     * @param int|callable $total
     *
     * @return Connection|Promise A connection or a promise
     */
    public function backward(ArgumentInterface $args, $total, array $callableArgs = [])
    {
        $total = $this->computeTotalCount($total, $callableArgs);
        $limit = $args['last'] ?? null;
        $before = $args['before'] ?? null;
        $offset = max(0, $this->connectionBuilder->getOffsetWithDefault($before, $total) - $limit);

        $entities = call_user_func($this->fetcher, $offset, $limit);

        return $this->handleEntities($entities, function ($entities) use ($args, $offset, $total) {
            return $this->connectionBuilder->connectionFromArraySlice($entities, $args, [
                'sliceStart' => $offset,
                'arrayLength' => $total,
            ]);
        });
    }

    /**
     * @return Connection|Promise A connection or a promise
     */
    public function forward(ArgumentInterface $args)
    {
        $limit = $args['first'] ?? null;
        $after = $args['after'] ?? null;
        $offset = $this->connectionBuilder->getOffsetWithDefault($after, 0);

        // If we don't have a cursor or if it's not valid, then we must not use the slice method
        if (!is_numeric($this->connectionBuilder->cursorToOffset($after)) || !$after) {
            $entities = call_user_func($this->fetcher, $offset, $limit ? $limit + 1 : $limit);

            return $this->handleEntities($entities, function ($entities) use ($args) {
                return $this->connectionBuilder->connectionFromArray($entities, $args);
            });
        } else {
            $entities = call_user_func($this->fetcher, $offset, $limit ? $limit + 2 : $limit);

            return $this->handleEntities($entities, function ($entities) use ($args, $offset) {
                return $this->connectionBuilder->connectionFromArraySlice($entities, $args, [
                    'sliceStart' => $offset,
                    'arrayLength' => $offset + count($entities),
                ]);
            });
        }
    }

    /**
     * @param int|callable $total
     *
     * @return Connection|Promise A connection or a promise
     */
    public function auto(ArgumentInterface $args, $total, array $callableArgs = [])
    {
        if (isset($args['last'])) {
            $connection = $this->backward($args, $total, $callableArgs);
        } else {
            $connection = $this->forward($args);
        }

        if ($this->promise) {
            /** @var Promise $connection */
            return $connection->then(function (ConnectionInterface $connection) use ($total, $callableArgs) {
                $connection->setTotalCount($this->computeTotalCount($total, $callableArgs));

                return $connection;
            });
        } else {
            /** @var Connection $connection */
            $connection->setTotalCount($this->computeTotalCount($total, $callableArgs));

            return $connection;
        }
    }

    /**
     * @param array<int, string>|Promise $entities An array of entities to paginate or a promise
     *
     * @return Connection|Promise A connection or a promise
     */
    private function handleEntities($entities, callable $callback)
    {
        if ($this->promise) {
            /** @var Promise $entities */
            return $entities->then($callback);
        }

        return $callback($entities);
    }

    /**
     * @param int|callable $total
     *
     * @return int|mixed
     */
    private function computeTotalCount($total, array $callableArgs = [])
    {
        if (isset($this->totalCount)) {
            return $this->totalCount;
        }

        $this->totalCount = is_callable($total) ? call_user_func_array($total, $callableArgs) : $total;

        return $this->totalCount;
    }
}
