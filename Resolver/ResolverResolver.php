<?php

namespace Overblog\GraphQLBundle\Resolver;

class ResolverResolver extends AbstractProxyResolver
{
    protected function unresolvableMessage($alias)
    {
        return sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias);
    }
}
