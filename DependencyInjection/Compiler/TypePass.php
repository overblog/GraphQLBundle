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

use Symfony\Component\DependencyInjection\ContainerBuilder;

class TypePass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graphql.type';
    }

    protected function getParameterName()
    {
        return 'overblog_graphql.types_mapping';
    }

    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        $mapping = $container->getParameter($this->getParameterName());

        $container->getDefinition('overblog_graphql.schema_builder')
            ->replaceArgument(1, $mapping);
    }
}
