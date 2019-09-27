<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class Resolver
{
    public static function defaultResolveFn($objectOrArray, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $value = static::valueFromObjectOrArray($objectOrArray, $fieldName);

        return $value instanceof \Closure ? $value($objectOrArray, $args, $context, $info) : $value;
    }

    public static function valueFromObjectOrArray($objectOrArray, $fieldName)
    {
        $value = null;
        if (\is_array($objectOrArray) && isset($objectOrArray[$fieldName])) {
            $value = $objectOrArray[$fieldName];
        } elseif (\is_object($objectOrArray)) {
            if (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, 'get')) {
                $value = $objectOrArray->$getter();
            } elseif (isset($objectOrArray->$fieldName)) {
                $value = $objectOrArray->$fieldName;
            }
        }

        return $value;
    }

    public static function setObjectOrArrayValue(&$objectOrArray, $fieldName, $value): void
    {
        if (\is_array($objectOrArray)) {
            $objectOrArray[$fieldName] = $value;
        } elseif (\is_object($objectOrArray)) {
            $objectOrArray->$fieldName = $value;
        }
    }

    private static function guessObjectMethod($object, string $fieldName, string $prefix): ?string
    {
        if (\is_callable([$object, $method = $prefix.\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $fieldName)))])) {
            return $method;
        }

        return null;
    }
}
