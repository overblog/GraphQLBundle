<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\QueryResolver;

class ResolverResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver(): QueryResolver
    {
        return new QueryResolver();
    }
}
