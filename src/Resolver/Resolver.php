<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Resolver
{
    /**
     * @var PropertyAccessor
     */
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
        $index = sprintf('[%s]', $fieldName);

        if (self::getAccessor()->isReadable($objectOrArray, $index)) {
            $value = self::getAccessor()->getValue($objectOrArray, $index);
        } elseif (is_object($objectOrArray)) {
            $value = self::propertyValueFromObject($objectOrArray, $fieldName);
        }

        return $value;
    }

    public static function setObjectOrArrayValue(&$objectOrArray, $fieldName, $value)
    {
        $index = sprintf('[%s]', $fieldName);

        if (self::getAccessor()->isWritable($objectOrArray, $index)) {
            self::getAccessor()->setValue($objectOrArray, $index, $value);
        } elseif (is_object($objectOrArray)) {
            self::getAccessor()->setValue($objectOrArray, $fieldName, $value);
        }
    }

    private static function propertyValueFromObject($object, $fieldName)
    {
        $value = null;

        if (self::getAccessor()->isReadable($object, $fieldName)) {
            $value = self::getAccessor()->getValue($object, $fieldName);
        }

        return $value;
    }

    private static function getAccessor()
    {
        if (null === self::$accessor) {
            self::$accessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$accessor;
    }
}
