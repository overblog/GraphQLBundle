<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class FieldResolver
{
    public function __invoke($parentValue, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $value = self::valueFromObjectOrArray($parentValue, $fieldName);

        return $value instanceof \Closure ? $value($parentValue, $args, $context, $info) : $value;
    }

    public static function valueFromObjectOrArray($objectOrArray, $fieldName)
    {
        $value = null;
        if (\is_array($objectOrArray) && isset($objectOrArray[$fieldName])) {
            $value = $objectOrArray[$fieldName];
        } elseif (\is_object($objectOrArray)) {
            if (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, 'get')) {
                $value = $objectOrArray->$getter();
            } elseif (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, 'is')) {
                $value = $objectOrArray->$getter();
            } elseif (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, '')) {
                $value = $objectOrArray->$getter();
            } elseif (isset($objectOrArray->$fieldName)) {
                $value = $objectOrArray->$fieldName;
            }
        }

        return $value;
    }

    private static function guessObjectMethod($object, string $fieldName, string $prefix): ?string
    {
        if (\is_callable([$object, $method = $prefix.\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $fieldName)))])) {
            return $method;
        }

        return null;
    }
}
