<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use function is_array;
use function is_callable;
use function is_object;
use function str_replace;
use function ucwords;

class FieldResolver
{
    /**
     * @param mixed $parentValue
     * @param mixed $args
     * @param mixed $context
     *
     * @return mixed|null
     */
    public function __invoke($parentValue, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $value = self::valueFromObjectOrArray($parentValue, $fieldName);

        return $value instanceof Closure ? $value($parentValue, $args, $context, $info) : $value;
    }

    /**
     * @param object|array $objectOrArray
     *
     * @return mixed|null
     */
    public static function valueFromObjectOrArray($objectOrArray, string $fieldName)
    {
        $value = null;
        if (is_array($objectOrArray) && isset($objectOrArray[$fieldName])) {
            $value = $objectOrArray[$fieldName];
        } elseif (is_object($objectOrArray)) {
            if (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, 'get')) {
                $value = $objectOrArray->$getter();
            } elseif (null !== $getter = self::guessObjectMethod($objectOrArray, $fieldName, '')) {
                $value = $objectOrArray->$getter();
            } elseif (isset($objectOrArray->$fieldName)) {
                $value = $objectOrArray->$fieldName;
            }
        }

        return $value;
    }

    private static function guessObjectMethod(object $object, string $fieldName, string $prefix): ?string
    {
        if (is_callable([$object, $method = $prefix.str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName)))])) {
            return $method;
        }

        return null;
    }
}
