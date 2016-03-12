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

class Resolver
{
    public static function defaultResolveFn($source, $args, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $property = null;

        $accessor = PropertyAccess::createPropertyAccessor();

        if (is_array($source) || $source instanceof \ArrayAccess) {
            $index = sprintf('[%s]', $fieldName);

            if ($accessor->isReadable($source, $index)) {
                $property = $accessor->getValue($source, $index);
            }
        } elseif (is_object($source)) {
            if ($accessor->isReadable($source, $fieldName)) {
                $property = $accessor->getValue($source, $fieldName);
            }
        }

        return $property instanceof \Closure ? $property($source) : $property;
    }
}
