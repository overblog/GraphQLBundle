<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class ResolverTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.resolver';
    }

    protected function checkRequirements($id, array $tag): void
    {
        parent::checkRequirements($id, $tag);

        if (isset($tag['method']) && !\is_string($tag['method'])) {
            throw new \InvalidArgumentException(
                \sprintf('Service tagged "%s" must have valid "method" argument.', $id)
            );
        }
    }

    protected function getResolverServiceID(): string
    {
        return 'overblog_graphql.resolver_resolver';
    }
}
