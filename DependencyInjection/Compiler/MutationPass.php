<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

class MutationPass extends ResolverPass
{
    protected function getTagName()
    {
        return 'overblog_graph.mutation';
    }

    protected function getParameterName()
    {
        return 'overblog_graph.mutations_mapping';
    }
}
