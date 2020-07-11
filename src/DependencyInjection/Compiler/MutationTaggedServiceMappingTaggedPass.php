<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class MutationTaggedServiceMappingTaggedPass extends ResolverTaggedServiceMappingPass
{
    protected function getTagName(): string
    {
        return 'overblog_graphql.mutation';
    }

    protected function getResolverServiceID(): string
    {
        return 'overblog_graphql.mutation_resolver';
    }
}
