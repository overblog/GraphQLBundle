<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Resolver\MutationResolver;

final class MutationResolverTest extends TestAbstractProxyResolver
{
    protected function createResolver(): MutationResolver
    {
        return new MutationResolver();
    }
}
