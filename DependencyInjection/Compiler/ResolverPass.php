<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class ResolverPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.resolver';
    }

    protected function getParameterName()
    {
        return 'overblog_graphql.resolvers_mapping';
    }

    protected function checkRequirements($id, array $tag)
    {
        parent::checkRequirements($id, $tag);

        if (!isset($tag['method']) || !is_string($tag['method'])) {
            throw new \InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "method" argument.', $id)
            );
        }
    }
}
