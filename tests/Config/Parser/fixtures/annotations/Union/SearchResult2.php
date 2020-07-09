<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Union;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Resolver\TypeResolver;

/**
 * @GQL\Union(types={"Hero", "Droid", "Sith"})
 */
class SearchResult2
{
    public static function resolveType(TypeResolver $typeResolver, bool $value): ?Type
    {
        return $typeResolver->resolve('Hero');
    }
}
