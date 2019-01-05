<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

/**
 * Class ConnectionBuilder.
 *
 * https://github.com/graphql/graphql-relay-js/blob/master/src/connection/arrayconnection.js
 */
class ConnectionBuilder
{
    public const PREFIX = 'arrayconnection:';

    /**
     * If set, used to generate the connection object.
     *
     * @var callable
     */
    protected $connectionCallback;

    /**
     * If set, used to generate the edge object.
     *
     * @var callable
     */
    protected $edgeCallback;

    public function __construct(callable $connectionCallback = null, callable $edgeCallback = null)
    {
        $this->connectionCallback = $connectionCallback;
        $this->edgeCallback = $edgeCallback;
    }

    /**
     * A simple function that accepts an array and connection arguments, and returns
     * a connection object for use in GraphQL. It uses array offsets as pagination,
     * so pagination will only work if the array is static.
     *
     * @param array          $data
     * @param array|Argument $args
     *
     * @return ConnectionInterface
     */
    public function connectionFromArray(array $data, $args = []): ConnectionInterface
    {
        return $this->connectionFromArraySlice(
            $data,
            $args,
            [
                'sliceStart' => 0,
                'arrayLength' => \count($data),
            ]
        );
    }

    /**
     * A version of `connectionFromArray` that takes a promised array, and returns a
     * promised connection.
     *
     * @param mixed          $dataPromise a promise
     * @param array|Argument $args
     *
     * @return mixed a promise
     */
    public function connectionFromPromisedArray($dataPromise, $args = [])
    {
        $this->checkPromise($dataPromise);

        return $dataPromise->then(function ($data) use ($args) {
            return $this->connectionFromArray($data, $args);
        });
    }

    /**
     * Given a slice (subset) of an array, returns a connection object for use in
     * GraphQL.
     *
     * This function is similar to `connectionFromArray`, but is intended for use
     * cases where you know the cardinality of the connection, consider it too large
     * to materialize the entire array, and instead wish pass in a slice of the
     * total result large enough to cover the range specified in `args`.
     *
     * @param array          $arraySlice
     * @param array|Argument $args
     * @param array          $meta
     *
     * @return ConnectionInterface
     */
    public function connectionFromArraySlice(array $arraySlice, $args, array $meta): ConnectionInterface
    {
        $connectionArguments = $this->getOptionsWithDefaults(
            $args instanceof Argument ? $args->getRawArguments() : $args,
            [
                'after' => '',
                'before' => '',
                'first' => null,
                'last' => null,
            ]
        );
        $arraySliceMetaInfo = $this->getOptionsWithDefaults(
            $meta,
            [
                'sliceStart' => 0,
                'arrayLength' => 0,
            ]
        );

        $arraySliceLength = \count($arraySlice);
        $after = $connectionArguments['after'];
        $before = $connectionArguments['before'];
        $first = $connectionArguments['first'];
        $last = $connectionArguments['last'];
        $sliceStart = $arraySliceMetaInfo['sliceStart'];
        $arrayLength = $arraySliceMetaInfo['arrayLength'];
        $sliceEnd = $sliceStart + $arraySliceLength;
        $beforeOffset = $this->getOffsetWithDefault($before, $arrayLength);
        $afterOffset = $this->getOffsetWithDefault($after, -1);

        $startOffset = \max($sliceStart - 1, $afterOffset, -1) + 1;
        $endOffset = \min($sliceEnd, $beforeOffset, $arrayLength);

        if (\is_numeric($first)) {
            if ($first < 0) {
                throw new \InvalidArgumentException('Argument "first" must be a non-negative integer');
            }
            $endOffset = \min($endOffset, $startOffset + $first);
        }

        if (\is_numeric($last)) {
            if ($last < 0) {
                throw new \InvalidArgumentException('Argument "last" must be a non-negative integer');
            }

            $startOffset = \max($startOffset, $endOffset - $last);
        }

        // If supplied slice is too large, trim it down before mapping over it.
        $offset = \max($startOffset - $sliceStart, 0);
        $length = ($arraySliceLength - ($sliceEnd - $endOffset)) - $offset;

        $slice = \array_slice(
            $arraySlice,
            $offset,
            $length
        );

        $edges = $this->createEdges($slice, $startOffset);

        $firstEdge = $edges[0] ?? null;
        $lastEdge = \end($edges);
        $lowerBound = $after ? ($afterOffset + 1) : 0;
        $upperBound = $before ? $beforeOffset : $arrayLength;

        $pageInfo = new PageInfo(
            $firstEdge instanceof EdgeInterface ? $firstEdge->getCursor() : null,
            $lastEdge instanceof EdgeInterface ? $lastEdge->getCursor() : null,
            null !== $last ? $startOffset > $lowerBound : false,
            null !== $first ? $endOffset < $upperBound : false
        );

        return $this->createConnection($edges, $pageInfo);
    }

    /**
     * A version of `connectionFromArraySlice` that takes a promised array slice,
     * and returns a promised connection.
     *
     * @param mixed          $dataPromise a promise
     * @param array|Argument $args
     * @param array          $meta
     *
     * @return mixed a promise
     */
    public function connectionFromPromisedArraySlice($dataPromise, $args, array $meta)
    {
        $this->checkPromise($dataPromise);

        return $dataPromise->then(function ($arraySlice) use ($args, $meta) {
            return $this->connectionFromArraySlice($arraySlice, $args, $meta);
        });
    }

    /**
     * Return the cursor associated with an object in an array.
     *
     * @param array $data
     * @param mixed $object
     *
     * @return string|null
     */
    public function cursorForObjectInConnection(array $data, $object): ? string
    {
        $offset = null;

        foreach ($data as $i => $entry) {
            // When using the comparison operator (==), object variables are compared in a simple manner,
            // namely: Two object instances are equal if they have the same attributes and values,
            // and are instances of the same class.
            if ($entry == $object) {
                $offset = $i;
                break;
            }
        }

        if (null === $offset) {
            return null;
        }

        return $this->offsetToCursor($offset);
    }

    /**
     * Given an optional cursor and a default offset, returns the offset
     * to use; if the cursor contains a valid offset, that will be used,
     * otherwise it will be the default.
     *
     * @param string|null $cursor
     * @param int         $defaultOffset
     *
     * @return int
     */
    public function getOffsetWithDefault(?string $cursor, int $defaultOffset): int
    {
        if (empty($cursor)) {
            return $defaultOffset;
        }
        $offset = $this->cursorToOffset($cursor);

        return !\is_numeric($offset) ? $defaultOffset : (int) $offset;
    }

    /**
     * Creates the cursor string from an offset.
     *
     * @param $offset
     *
     * @return string
     */
    public function offsetToCursor($offset): string
    {
        return \base64_encode(static::PREFIX.$offset);
    }

    /**
     * Redefines the offset from the cursor string.
     *
     * @param $cursor
     *
     * @return string
     */
    public function cursorToOffset($cursor): string
    {
        if (null === $cursor) {
            return '';
        }

        return \str_replace(static::PREFIX, '', \base64_decode($cursor, true));
    }

    private function createEdges(iterable $slice, int $startOffset): array
    {
        $edges = [];

        foreach ($slice as $index => $value) {
            $cursor = $this->offsetToCursor($startOffset + $index);
            if ($this->edgeCallback) {
                $edge = ($this->edgeCallback)($cursor, $value, $index);
                if (!($edge instanceof EdgeInterface)) {
                    throw new \InvalidArgumentException(\sprintf('The $edgeCallback of the ConnectionBuilder must return an instance of EdgeInterface'));
                }
            } else {
                $edge = new Edge($cursor, $value);
            }
            $edges[] = $edge;
        }

        return $edges;
    }

    private function createConnection($edges, PageInfoInterface $pageInfo): ConnectionInterface
    {
        if ($this->connectionCallback) {
            $connection = ($this->connectionCallback)($edges, $pageInfo);
            if (!($connection instanceof ConnectionInterface)) {
                throw new \InvalidArgumentException(\sprintf('The $connectionCallback of the ConnectionBuilder must return an instance of ConnectionInterface'));
            }

            return $connection;
        }

        return new Connection($edges, $pageInfo);
    }

    private function getOptionsWithDefaults(array $options, array $defaults)
    {
        return $options + $defaults;
    }

    private function checkPromise($value): void
    {
        if (!\is_callable([$value, 'then'])) {
            throw new \InvalidArgumentException('This is not a valid promise.');
        }
    }
}
