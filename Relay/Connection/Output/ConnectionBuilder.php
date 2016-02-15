<?php

namespace Overblog\GraphQLBundle\Relay\Connection\Output;


use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ConnectionBuilder
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/arrayconnection.js
 */
class ConnectionBuilder
{
    const PREFIX = 'arrayconnection:';

    /**
     * A simple function that accepts an array and connection arguments, and returns
     * a connection object for use in GraphQL. It uses array offsets as pagination,
     * so pagination will only work if the array is static.
     *
     * @param array $data
     * @param array $args
     * @return Connection
     */
    public static function connectionFromArray($data, array $args = [])
    {
        return static::connectionFromArraySlice(
            $data,
            $args,
            [
                'sliceStart' => 0,
                'arrayLength' => count($data),
            ]
        );
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
     * @param array $arraySlice
     * @param array $args
     * @param array $meta
     *
     * @return Connection
     */
    public static function connectionFromArraySlice($arraySlice, array $args, array $meta)
    {
        $connectionArguments = static::getOptionsWithDefaults(
            $args,
            [
                'after' => '',
                'before' => '',
                'first' => null,
                'last' => null,
            ]
        );
        $arraySliceMetaInfo = static::getOptionsWithDefaults(
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
        $beforeOffset = static::getOffsetWithDefault($before, $arrayLength);
        $afterOffset = static::getOffsetWithDefault($after, -1);

        $startOffset = max($sliceStart - 1, $afterOffset, -1) + 1;
        $endOffset = min($sliceEnd, $beforeOffset, $arrayLength);

        if (is_numeric($first)) {
            $endOffset = min($endOffset, $startOffset + $first);
        }
        if (is_numeric($last)) {
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

        $edges = [];

        foreach($slice as $index => $value) {
            $edges[] = new Edge(static::offsetToCursor($startOffset + $index), $value);
        }

        $firstEdge = isset($edges[0]) ? $edges[0] : null;
        $lastEdge = end($edges);
        $lowerBound = $after ? ($afterOffset + 1) : 0;
        $upperBound = $before ? $beforeOffset : $arrayLength;

        return new Connection(
            $edges,
            new PageInfo (
                $firstEdge instanceof Edge ? $firstEdge->cursor : null,
                $lastEdge instanceof Edge  ? $lastEdge->cursor : null,
                $last !== null ? $startOffset > $lowerBound : false,
                $first !== null ? $endOffset < $upperBound : false
            )
        );
    }

    /**
     * Return the cursor associated with an object in an array.
     * @param array $data
     * @param mixed $object
     * @return null|string
     */
    public static function cursorForObjectInConnection($data, $object)
    {
        $offset = null;

        foreach($data as $i => $entry) {
            if ($entry == $object) {
                $offset = $i;
                break;
            }
        }

        if (null === $offset) {
            return null;
        }

        return static::offsetToCursor($offset);
    }

    /**
     * Given an optional cursor and a default offset, returns the offset
     * to use; if the cursor contains a valid offset, that will be used,
     * otherwise it will be the default.
     *
     * @param string $cursor
     * @param int    $defaultOffset
     * @return int
     */
    public static function getOffsetWithDefault($cursor, $defaultOffset)
    {
        if (empty($cursor)) {
            return $defaultOffset;
        }
        $offset = static::cursorToOffset($cursor);

        return !is_numeric($offset) ?  $defaultOffset : (int)$offset;
    }

    /**
     * Creates the cursor string from an offset.
     * @param $offset
     * @return string
     */
    public static function offsetToCursor($offset)
    {
        return base64_encode(static::PREFIX . $offset);
    }

    /**
     * Redefines the offset from the cursor string.
     * @param $cursor
     * @return int
     */
    public static function cursorToOffset($cursor)
    {
        return str_replace(static::PREFIX, '', base64_decode($cursor, true));
    }

    private static function getOptionsWithDefaults(array $options, array $defaults)
    {
        $arraySliceResolver = new OptionsResolver();
        $arraySliceResolver->setDefaults($defaults);

        return $arraySliceResolver->resolve($options);
    }

}
