<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class ArgPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.arg';
    }

    protected function getParameterName()
    {
        return 'overblog_graphql.args_mapping';
    }
}
