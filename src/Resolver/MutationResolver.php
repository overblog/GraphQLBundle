<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use function sprintf;

class MutationResolver extends AbstractProxyResolver
{
    protected function unresolvableMessage(string $alias): string
    {
        return sprintf('Unknown mutation with alias "%s" (verified service tag)', $alias);
    }
}
