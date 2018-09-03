<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

class MutationResolver extends AbstractProxyResolver
{
    protected function unresolvableMessage($alias)
    {
        return \sprintf('Unknown mutation with alias "%s" (verified service tag)', $alias);
    }
}
