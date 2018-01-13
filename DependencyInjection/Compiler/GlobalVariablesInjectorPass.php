<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\GlobalVariablesInjectorConfigProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class GlobalVariablesInjectorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(GlobalVariablesInjectorConfigProcessor::class);
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.global_variable', true);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias']) || !is_string($attributes['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Service "%s" tagged "overblog_graphql.global_variable" should have a valid "alias" attribute.', $id)
                    );
                }

                $definition->addMethodCall(
                    'addGlobalVariable',
                    [
                        $attributes['alias'],
                        new Reference($id),
                        isset($attributes['public']) ? (bool) $attributes['public'] : true,
                    ]
                );
            }
        }
    }
}
