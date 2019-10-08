<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigProcessorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(ConfigProcessor::class);

        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.definition_config_processor');

        $arguments = [];
        foreach ($taggedServices as $id => $tags) {
            $arguments[] = new Reference($id);
        }

        $definition->setArgument('$processors', $arguments);
    }
}
