<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

class MutationResolver extends ResolverResolver
{
    protected function getMapping()
    {
        return $this->container->getParameter('overblog_graphql.mutations_mapping');
    }

    protected function unresolvableMessage($alias)
    {
        return sprintf('Unknown mutation with alias "%s" (verified service tag)', $alias);
    }
}
