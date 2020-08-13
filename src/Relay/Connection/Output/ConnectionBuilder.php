<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder as BaseBuilder;
use function func_get_args;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

@trigger_error(
    'Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder was moved to Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder since 0.12 and will be removed in 0.13.',
    E_USER_DEPRECATED
);

class ConnectionBuilder
{
    public static function connectionFromArray(array $data, array $args = []): Connection
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $dataPromise
     *
     * @return mixed
     */
    public static function connectionFromPromisedArray($dataPromise, array $args = [])
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    public static function connectionFromArraySlice(array $arraySlice, array $args, array $meta): Connection
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $dataPromise
     *
     * @return mixed
     */
    public static function connectionFromPromisedArraySlice($dataPromise, array $args, array $meta)
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    public static function cursorForObjectInConnection(array $data, string $object): ?string
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    public static function getOffsetWithDefault(?string $cursor, int $defaultOffset): int
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int|string $offset
     */
    public static function offsetToCursor($offset): string
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    public static function cursorToOffset(string $cursor): string
    {
        return self::call(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    private static function call(string $func, array $arguments)
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new BaseBuilder();
        }

        @trigger_error(
            sprintf(
                'Calling static method %s::%s is deprecated as of 0.12 and will be removed in 0.13. '.
                'You should use an object instance of %s to access "%s" method.',
                __CLASS__,
                $func,
                BaseBuilder::class,
                $func
            ),
            E_USER_DEPRECATED
        );

        return $instance->$func(...$arguments);
    }
}
