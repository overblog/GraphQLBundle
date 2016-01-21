<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

class TypePass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graph.type';
    }

    protected function getParameterName()
    {
        return 'overblog_graph.types_mapping';
    }
}
