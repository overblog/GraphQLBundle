<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class MutationTaggedServiceMappingTaggedPass extends ResolverTaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.mutation';
    }

    protected function getResolverServiceID()
    {
        return 'overblog_graphql.mutation_resolver';
    }
}
