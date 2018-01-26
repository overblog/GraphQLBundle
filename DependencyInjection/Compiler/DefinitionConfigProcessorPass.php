<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DefinitionConfigProcessorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(ConfigProcessor::class);
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.definition_config_processor', true);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addConfigProcessor',
                    [
                        new Reference($id),
                        isset($attributes['priority']) ? $attributes['priority'] : 0,
                    ]
                );
            }
        }
    }
}
