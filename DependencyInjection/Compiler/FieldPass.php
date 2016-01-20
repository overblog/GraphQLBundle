<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class FieldPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $graphFieldsMapping = [];

        $taggedServices = $container->findTaggedServiceIds('overblog_graph.field');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $graphFieldsMapping[$tag['alias']] = $id;
            }
        }

        $container->setParameter('overblog_graph.fields_mapping', $graphFieldsMapping);
    }
}
