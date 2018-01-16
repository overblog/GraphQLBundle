<?php

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MutationResolverTest extends AbstractProxyResolverTest
{
    protected function createResolver(EventDispatcherInterface $eventDispatcher)
    {
        return new MutationResolver($eventDispatcher);
    }
}
