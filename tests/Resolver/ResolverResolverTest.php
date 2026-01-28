<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\QueryResolver;

final class ResolverResolverTest extends TestAbstractProxyResolver
{
    protected function createResolver(): QueryResolver
    {
        return new QueryResolver();
    }
}
