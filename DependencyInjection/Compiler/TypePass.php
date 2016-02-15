<?php

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
