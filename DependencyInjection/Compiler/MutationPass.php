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
