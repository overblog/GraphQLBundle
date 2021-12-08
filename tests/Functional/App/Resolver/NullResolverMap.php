<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverMap;

final class NullResolverMap extends ResolverMap
{
    protected function map(): array
    {
        return [];
    }
}
