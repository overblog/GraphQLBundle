<?php

namespace Overblog\GraphBundle\Resolver;

class MutationResolver extends ResolverResolver
{
    protected function getMapping()
    {
        return $this->container->getParameter('overblog_graph.mutations_mapping');
    }

    protected function unresolvableMessage($alias)
    {
        return sprintf('Unknown mutation with alias "%s" (verified service tag)', $alias);
    }
}
