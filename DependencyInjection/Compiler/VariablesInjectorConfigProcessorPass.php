<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\ConfigProcessor\VariablesInjectorConfigProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

final class VariablesInjectorConfigProcessorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->register(VariablesInjectorConfigProcessor::class, VariablesInjectorConfigProcessor::class)
            ->addTag('overblog_graphql.definition_config_processor', ['priority' => 1024])
            ->setArguments([new Reference('overblog_graphql.expression_language')])
        ;

        foreach (['container' => 'service_container', 'token' => 'security.token_storage', 'request' => 'request_stack'] as $name => $id) {
            $definition->addMethodCall('addVariable', [$name, new Reference($id, ContainerInterface::NULL_ON_INVALID_REFERENCE)]);
        }
    }
}
