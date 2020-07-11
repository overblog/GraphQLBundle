<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverResolver;

class ResolverResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver(): ResolverResolver
    {
        return new ResolverResolver();
    }
}
