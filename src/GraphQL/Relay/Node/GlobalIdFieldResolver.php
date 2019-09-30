<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\GraphQL\Relay\Node;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\FieldResolver;

final class GlobalIdFieldResolver implements ResolverInterface, AliasedInterface
{
    public function __invoke($obj, ResolveInfo $info, $idValue, $typeName)
    {
        return GlobalId::toGlobalId(
            !empty($typeName) ? $typeName : $info->parentType->name,
            $idValue ? $idValue : FieldResolver::valueFromObjectOrArray($obj, 'id')
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['__invoke' => 'relay_globalid_field'];
    }
}
