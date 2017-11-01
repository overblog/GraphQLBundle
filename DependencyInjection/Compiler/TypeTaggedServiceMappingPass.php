<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class TypeTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    const TAG_NAME = 'overblog_graphql.type';

    protected function getTagName()
    {
        return self::TAG_NAME;
    }

    protected function getResolverServiceID()
    {
        return 'overblog_graphql.type_resolver';
    }
}
