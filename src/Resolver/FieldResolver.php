<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use function is_array;
use function is_callable;
use function is_object;
use function str_replace;

class FieldResolver
{
    /**
     * Allowed method prefixes
     */
    private const PREFIXES = ['get', 'is', 'has', ''];

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
        $value = static::valueFromObjectOrArray($parentValue, $fieldName);

        return $value instanceof Closure ? $value($parentValue, $args, $context, $info) : $value;
    }

    /**
     * @param object|array $objectOrArray
     *
     * @return mixed|null
     */
    public static function valueFromObjectOrArray($objectOrArray, string $fieldName)
    {
        if (is_array($objectOrArray) && isset($objectOrArray[$fieldName])) {
            return $objectOrArray[$fieldName];
        }

        if (is_object($objectOrArray)) {
            foreach (static::PREFIXES as $prefix) {
                $method = $prefix.str_replace('_', '', $fieldName);

                if (is_callable([$objectOrArray, $method])) {
                    return $objectOrArray->$method();
                }
            }

            if (isset($objectOrArray->$fieldName)) {
                return $objectOrArray->$fieldName;
            }
        }

        return null;
    }
}
