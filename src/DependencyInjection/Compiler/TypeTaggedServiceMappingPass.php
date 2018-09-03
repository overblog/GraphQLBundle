<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class TypeTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    public const TAG_NAME = 'overblog_graphql.type';

    protected function getTagName()
    {
        return self::TAG_NAME;
    }

    protected function getResolverServiceID(): string
    {
        return 'overblog_graphql.type_resolver';
    }
}
