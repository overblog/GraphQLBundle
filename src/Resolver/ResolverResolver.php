<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use function sprintf;

class ResolverResolver extends AbstractProxyResolver
{
    protected function unresolvableMessage(string $alias): string
    {
        return sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias);
    }
}
