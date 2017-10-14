<?php

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
