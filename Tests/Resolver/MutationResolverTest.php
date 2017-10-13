<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\MutationResolver;

class MutationResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver()
    {
        return new MutationResolver();
    }
}
