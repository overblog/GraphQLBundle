<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class GlobalVariablesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.global_variable', true);
        $globalVariables = ['container' => new Reference('service_container')];
        $expressionLanguageDefinition = $container->findDefinition('overblog_graphql.expression_language');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias']) || !\is_string($attributes['alias'])) {
                    throw new \InvalidArgumentException(
                        \sprintf('Service "%s" tagged "overblog_graphql.global_variable" should have a valid "alias" attribute.', $id)
                    );
                }
                $globalVariables[$attributes['alias']] = new Reference($id);

                $isPublic = isset($attributes['public']) ? (bool) $attributes['public'] : true;
                if ($isPublic) {
                    $expressionLanguageDefinition->addMethodCall(
                        'addGlobalName',
                        [
                            \sprintf('globalVariable->get(\'%s\')', $attributes['alias']),
                            $attributes['alias'],
                        ]
                    );
                }
            }
        }
        $container->findDefinition(GlobalVariables::class)->setArguments([$globalVariables]);
    }
}
