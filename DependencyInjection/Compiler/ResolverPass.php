<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

class ResolverPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graph.resolver';
    }

    protected function getParameterName()
    {
        return 'overblog_graph.resolvers_mapping';
    }
}
