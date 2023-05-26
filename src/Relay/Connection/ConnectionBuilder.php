<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Relay\Connection\Cursor\Base64CursorEncoder;
use Overblog\GraphQLBundle\Relay\Connection\Cursor\CursorEncoderInterface;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

use function array_slice;
use function count;
use function end;
use function is_callable;
use function is_numeric;
use function max;
use function min;
use function str_replace;

/**
 * Class ConnectionBuilder.
 *
 * https://github.com/graphql/graphql-relay-js/blob/master/src/connection/arrayconnection.js
 *
 * @phpstan-type ConnectionFactoryFunc callable(EdgeInterface<T>[], PageInfoInterface): ConnectionInterface
 * @phpstan-type EdgeFactoryFunc callable(string, T, int): EdgeInterface<T>
 *
 * @phpstan-template T
 */
final class ConnectionBuilder
{
    public const PREFIX = 'arrayconnection:';

    private CursorEncoderInterface $cursorEncoder;

    /**
     * Factorty callback used to generate the connection object.
     *
     * @var callable
     *
     * @phpstan-var ConnectionFactoryFunc
     */
    private $connectionCallback;

    /**
     * Factorty callback used to generate the edge object.
     *
     * @var callable
     *
     * @phpstan-var EdgeFactoryFunc
     */
    private $edgeCallback;

    /**
     * @phpstan-param ConnectionFactoryFunc|null $connectionCallback
     * @phpstan-param EdgeFactoryFunc|null $edgeCallback
     */
    public function __construct(CursorEncoderInterface $cursorEncoder = null, callable $connectionCallback = null, callable $edgeCallback = null)
    {
        $this->cursorEncoder = $cursorEncoder ?? new Base64CursorEncoder();
        $this->connectionCallback = $connectionCallback ?? static fn (array $edges, PageInfoInterface $pageInfo): Connection => new Connection($edges, $pageInfo);
        $this->edgeCallback = $edgeCallback ?? static fn (string $cursor, mixed $value): Edge => new Edge($cursor, $value);
    }

    /**
     * A simple function that accepts an array and connection arguments, and returns
     * a connection object for use in GraphQL. It uses array offsets as pagination,
     * so pagination will only work if the array is static.
     *
     * @param array|ArgumentInterface $args
     *
     * @phpstan-param T[] $data
     *
     * @return ConnectionInterface<T>
     */
    public function connectionFromArray(array $data, $args = []): ConnectionInterface
    {
        return $this->connectionFromArraySlice(
            $data,
            $args,
            [
                'sliceStart' => 0,
                'arrayLength' => count($data),
            ]
        );
    }

    /**
     * A version of `connectionFromArray` that takes a promised array, and returns a
     * promised connection.
     *
     * @param mixed                   $dataPromise a promise
     * @param array|ArgumentInterface $args
     *
     * @return mixed a promise
     */
    public function connectionFromPromisedArray($dataPromise, $args = [])
    {
        $this->checkPromise($dataPromise);

        return $dataPromise->then(fn ($data) => $this->connectionFromArray($data, $args));
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
     * @param array|ArgumentInterface $args
     *
     * @phpstan-param T[] $arraySlice
     *
     * @phpstan-return ConnectionInterface<T>
     */
    public function connectionFromArraySlice(array $arraySlice, $args, array $meta): ConnectionInterface
    {
        $connectionArguments = $this->getOptionsWithDefaults(
            $args instanceof ArgumentInterface ? $args->getArrayCopy() : $args,
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

        $arraySliceLength = count($arraySlice);
        $after = $connectionArguments['after'];
        $before = $connectionArguments['before'];
        $first = $connectionArguments['first'];
        $last = $connectionArguments['last'];
        $sliceStart = $arraySliceMetaInfo['sliceStart'];
        $arrayLength = $arraySliceMetaInfo['arrayLength'];
        $sliceEnd = $sliceStart + $arraySliceLength;
        $beforeOffset = $this->getOffsetWithDefault($before, $arrayLength);
        $afterOffset = $this->getOffsetWithDefault($after, -1);

        if ($afterOffset > $beforeOffset) {
            throw new InvalidArgumentException('Arguments "before" and "after" cannot be intersected');
        }

        $startOffset = max($sliceStart - 1, $afterOffset, -1) + 1;
        $endOffset = min($sliceEnd, $beforeOffset, $arrayLength);

        if (is_numeric($first)) {
            if ($first < 0) {
                throw new InvalidArgumentException('Argument "first" must be a non-negative integer');
            }
            $endOffset = min($endOffset, $startOffset + $first); // @phpstan-ignore-line
        }

        if (is_numeric($last)) {
            if ($last < 0) {
                throw new InvalidArgumentException('Argument "last" must be a non-negative integer');
            }

            $startOffset = max($startOffset, $endOffset - $last);
        }

        // If supplied slice is too large, trim it down before mapping over it.
        $offset = max($startOffset - $sliceStart, 0);
        $length = ($arraySliceLength - ($sliceEnd - $endOffset)) - $offset;

        $slice = array_slice(
            $arraySlice,
            $offset,
            $length
        );

        $edges = $this->createEdges($slice, $startOffset);

        $firstEdge = $edges[0] ?? null;
        $lastEdge = end($edges);

        $pageInfo = new PageInfo(
            $firstEdge instanceof EdgeInterface ? $firstEdge->getCursor() : null,
            $lastEdge instanceof EdgeInterface ? $lastEdge->getCursor() : null,
            $startOffset > 0,
            $endOffset < $arrayLength
        );

        return $this->createConnection($edges, $pageInfo);
    }

    /**
     * A version of `connectionFromArraySlice` that takes a promised array slice,
     * and returns a promised connection.
     *
     * @param mixed                   $dataPromise a promise
     * @param array|ArgumentInterface $args
     *
     * @return mixed a promise
     */
    public function connectionFromPromisedArraySlice($dataPromise, $args, array $meta)
    {
        $this->checkPromise($dataPromise);

        return $dataPromise->then(fn ($arraySlice) => $this->connectionFromArraySlice($arraySlice, $args, $meta));
    }

    /**
     * Return the cursor associated with an object in an array.
     *
     * @param mixed $object
     */
    public function cursorForObjectInConnection(array $data, $object): ?string
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
     */
    public function getOffsetWithDefault(?string $cursor, int $defaultOffset): int
    {
        if (empty($cursor)) {
            return $defaultOffset;
        }
        $offset = $this->cursorToOffset($cursor);

        return !is_numeric($offset) ? $defaultOffset : (int) $offset;
    }

    /**
     * Creates the cursor string from an offset.
     *
     * @param int|string $offset
     */
    public function offsetToCursor($offset): string
    {
        return $this->cursorEncoder->encode(self::PREFIX.$offset);
    }

    /**
     * Redefines the offset from the cursor string.
     */
    public function cursorToOffset(?string $cursor): string
    {
        // Returning an empty string is required to not break the Paginator
        // class. Ideally, we should throw an exception or not call this
        // method if $cursor is empty
        if (null === $cursor) {
            return '';
        }

        return str_replace(static::PREFIX, '', $this->cursorEncoder->decode($cursor));
    }

    /**
     * @phpstan-param iterable<T> $slice
     *
     * @phpstan-return EdgeInterface<T>[]
     */
    private function createEdges(iterable $slice, int $startOffset): array
    {
        $edges = [];

        foreach ($slice as $index => $value) {
            $cursor = $this->offsetToCursor($startOffset + $index);
            $edge = ($this->edgeCallback)($cursor, $value, $index);

            if (!($edge instanceof EdgeInterface)) {
                throw new InvalidArgumentException('The $edgeCallback of the ConnectionBuilder must return an instance of EdgeInterface');
            }

            $edges[] = $edge;
        }

        return $edges;
    }

    /**
     * @phpstan-param EdgeInterface<T>[] $edges
     *
     * @phpstan-return ConnectionInterface<T>
     */
    private function createConnection(array $edges, PageInfoInterface $pageInfo): ConnectionInterface
    {
        $connection = ($this->connectionCallback)($edges, $pageInfo);

        if (!($connection instanceof ConnectionInterface)) {
            throw new InvalidArgumentException('The $connectionCallback of the ConnectionBuilder must return an instance of ConnectionInterface');
        }

        return $connection;
    }

    private function getOptionsWithDefaults(array $options, array $defaults): array
    {
        return $options + $defaults;
    }

    /**
     * @param string|object $value
     */
    private function checkPromise($value): void
    {
        if (!is_callable([$value, 'then'])) {
            throw new InvalidArgumentException('This is not a valid promise.');
        }
    }
}
