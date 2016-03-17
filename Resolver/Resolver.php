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

    public static function defaultResolveFn($source, $args, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $property = null;

        $index = sprintf('[%s]', $fieldName);

        if (self::getAccessor()->isReadable($source, $index)) {
            $property = self::getAccessor()->getValue($source, $index);
        } elseif (is_object($source)) {
            $property = self::propertyValueFromObject($source, $fieldName);
        }

        return $property instanceof \Closure ? $property($source, $args, $info) : $property;
    }

    private static function propertyValueFromObject($object, $fieldName)
    {
        $property = null;

        // accessor try to access the value using methods
        // first before using public property directly
        // not what we wont here!
        if (isset($object->{$fieldName})) {
            $property = $object->{$fieldName};
        } elseif (self::getAccessor()->isReadable($object, $fieldName)) {
            $property = self::getAccessor()->getValue($object, $fieldName);
        }

        return $property;
    }

    private static function getAccessor()
    {
        if (null === self::$accessor) {
            self::$accessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$accessor;
    }
}
