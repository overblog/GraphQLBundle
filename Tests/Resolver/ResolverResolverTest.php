<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ResolverResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver(EventDispatcherInterface $eventDispatcher)
    {
        return new ResolverResolver($eventDispatcher);
    }
}
