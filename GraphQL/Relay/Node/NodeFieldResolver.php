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

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

final class NodeFieldResolver implements ResolverInterface, AliasedInterface
{
    public function __invoke($args, $context, $info, \Closure $idFetcherCallback)
    {
        return $idFetcherCallback($args['id'], $context, $info);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases()
    {
        return ['__invoke' => 'relay_node_field'];
    }
}
