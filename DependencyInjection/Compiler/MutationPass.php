<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class MutationPass extends ResolverPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.mutation';
    }

    protected function getParameterName()
    {
        return 'overblog_graphql.mutations_mapping';
    }
}
