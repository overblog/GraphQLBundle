<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder as BaseBuilder;

@\trigger_error(
    'Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder was moved to Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder since 0.12 and will be removed in 0.13.',
    \E_USER_DEPRECATED
);

class ConnectionBuilder
{
    public static function connectionFromArray(array $data, $args = []): Connection
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function connectionFromPromisedArray($dataPromise, $args = [])
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function connectionFromArraySlice(array $arraySlice, $args, array $meta): Connection
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function connectionFromPromisedArraySlice($dataPromise, $args, array $meta)
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function cursorForObjectInConnection(array $data, $object): ?string
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function getOffsetWithDefault(?string $cursor, int $defaultOffset): int
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function offsetToCursor($offset): string
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    public static function cursorToOffset($cursor): string
    {
        return self::call(__FUNCTION__, \func_get_args());
    }

    private static function call(string $func, array $arguments)
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new BaseBuilder();
        }

        @\trigger_error(
            \sprintf(
                'Calling static method %s::%s is deprecated as of 0.12 and will be removed in 0.13. '.
                'You should use an object instance of %s to access "%s" method.',
                __CLASS__,
                $func,
                BaseBuilder::class,
                $func
            ),
            \E_USER_DEPRECATED
        );

        return $instance->$func(...$arguments);
    }
}
