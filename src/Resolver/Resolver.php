<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Resolver
{
    /** @var PropertyAccessor */
    private static $accessor;

    public static function defaultResolveFn($objectOrArray, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $value = static::valueFromObjectOrArray($objectOrArray, $fieldName);

        return $value instanceof \Closure ? $value($objectOrArray, $args, $context, $info) : $value;
    }

    public static function valueFromObjectOrArray($objectOrArray, $fieldName)
    {
        $value = null;
        $index = \sprintf('[%s]', $fieldName);

        if (self::isReadable($objectOrArray, $index)) {
            $value = self::getAccessor()->getValue($objectOrArray, $index);
        } elseif (\is_object($objectOrArray) && self::isReadable($objectOrArray, $fieldName)) {
            $value = self::getAccessor()->getValue($objectOrArray, $fieldName);
        }

        return $value;
    }

    public static function setObjectOrArrayValue(&$objectOrArray, $fieldName, $value)
    {
        $index = \sprintf('[%s]', $fieldName);

        if (self::isWritable($objectOrArray, $index)) {
            self::getAccessor()->setValue($objectOrArray, $index, $value);
        } elseif (\is_object($objectOrArray) && self::isWritable($objectOrArray, $fieldName)) {
            self::getAccessor()->setValue($objectOrArray, $fieldName, $value);
        }
    }

    public static function wrapArgs(callable $resolver)
    {
        return static function () use ($resolver) {
            $args = \func_get_args();
            if (\count($args) > 1 && !$args[1] instanceof Argument) {
                $args[1] = new Argument($args[1]);
            }

            return $resolver(...$args);
        };
    }

    private static function isReadable($objectOrArray, $indexOrProperty)
    {
        return self::getAccessor()->isReadable($objectOrArray, $indexOrProperty);
    }

    private static function isWritable($objectOrArray, $indexOrProperty)
    {
        return self::getAccessor()->isWritable($objectOrArray, $indexOrProperty);
    }

    private static function getAccessor()
    {
        if (null === self::$accessor) {
            self::$accessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$accessor;
    }
}
