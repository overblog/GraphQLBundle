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

class TypeTaggedServiceMappingPass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.type';
    }

    protected function getResolverServiceID()
    {
        return 'overblog_graphql.type_resolver';
    }
}
