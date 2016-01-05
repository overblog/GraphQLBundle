<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TypePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $graphTypesMapping = [];

        $taggedServices = $container->findTaggedServiceIds('overblog_graph.type');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $graphTypesMapping[$tag["alias"]] = $id;
            }
        }

        $container->setParameter('overblog_graph.types_mapping', $graphTypesMapping);
    }
}
