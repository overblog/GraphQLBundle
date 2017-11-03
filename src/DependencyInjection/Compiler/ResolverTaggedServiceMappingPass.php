<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

class ResolverTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.resolver';
    }

    protected function checkRequirements($id, array $tag)
    {
        parent::checkRequirements($id, $tag);

        if (isset($tag['method']) && !is_string($tag['method'])) {
            throw new \InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "method" argument.', $id)
            );
        }
    }

    protected function getResolverServiceID()
    {
        return 'overblog_graphql.resolver_resolver';
    }
}
