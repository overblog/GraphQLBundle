<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\GraphQL\Relay\Node;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

final class NodeFieldResolver implements ResolverInterface, AliasedInterface
{
    /**
     * @param mixed $context
     *
     * @return mixed
     */
    public function __invoke(ArgumentInterface $args, $context, ResolveInfo $info, Closure $idFetcherCallback)
    {
        return $idFetcherCallback($args['id'], $context, $info);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['__invoke' => 'relay_node_field'];
    }
}
