<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class TypeTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.type';
    }

    protected function getResolverServiceID()
    {
        return 'overblog_graphql.type_resolver';
    }
}
