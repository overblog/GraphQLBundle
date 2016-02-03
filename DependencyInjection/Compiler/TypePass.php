<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class TypePass extends TaggedServiceMappingPass
{
    protected function getTagName()
    {
        return 'overblog_graph.type';
    }

    protected function getParameterName()
    {
        return 'overblog_graph.types_mapping';
    }

    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        $mapping = $container->getParameter($this->getParameterName());

        $container->getDefinition('overblog_graph.schema_builder')
            ->replaceArgument(1, $mapping);
    }
}
