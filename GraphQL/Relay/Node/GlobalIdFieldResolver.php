<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\GraphQL\Relay\Node;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\Resolver;

final class GlobalIdFieldResolver implements ResolverInterface, AliasedInterface
{
    public function __invoke($obj, ResolveInfo $info, $idValue, $typeName)
    {
        return GlobalId::toGlobalId(
            !empty($typeName) ? $typeName : $info->parentType->name,
            $idValue ? $idValue : Resolver::valueFromObjectOrArray($obj, 'id')
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases()
    {
        return ['__invoke' => 'relay_globalid_field'];
    }
}
