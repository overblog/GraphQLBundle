<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverResolver;

class ResolverResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver()
    {
        return new ResolverResolver();
    }
}
