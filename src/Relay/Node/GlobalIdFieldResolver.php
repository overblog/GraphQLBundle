<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Node;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Resolver\Resolver;

class GlobalIdFieldResolver
{
    public function resolve($obj, ResolveInfo $info, $idValue, $typeName)
    {
        return GlobalId::toGlobalId(
            !empty($typeName) ? $typeName : $info->parentType->name,
            $idValue ? $idValue : Resolver::valueFromObjectOrArray($obj, 'id')
        );
    }
}
