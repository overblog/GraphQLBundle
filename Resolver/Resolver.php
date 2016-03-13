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

        if (null === self::$accessor) {
            self::$accessor = PropertyAccess::createPropertyAccessor();
        }

        $index = sprintf('[%s]', $fieldName);

        if (self::$accessor->isReadable($source, $index)) {
            $property = self::$accessor->getValue($source, $index);
        } elseif (self::$accessor->isReadable($source, $fieldName)) {
            $property = self::$accessor->getValue($source, $fieldName);
        }

        return $property instanceof \Closure ? $property($source, $args, $info) : $property;
    }
}
