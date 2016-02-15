<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class FieldPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.field';
    }

    protected function getParameterName()
    {
        return 'overblog_graphql.fields_mapping';
    }
}
