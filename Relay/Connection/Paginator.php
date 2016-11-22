<?php

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

        // The extra fetched element is here to determine if there is a next page.
        $entities = call_user_func($this->fetcher, $offset, $limit + 1);

        return ConnectionBuilder::connectionFromArraySlice($entities, $args, [
            'sliceStart' => $offset,
            'arrayLength' => $offset + count($entities),
        ]);
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
