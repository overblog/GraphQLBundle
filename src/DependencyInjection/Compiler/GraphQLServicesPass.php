<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use function is_string;
use function sprintf;

final class GraphQLServicesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('overblog_graphql.service', true);

        $locateableServices = [];
        $expressionLanguageDefinition = $container->findDefinition('overblog_graphql.expression_language');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $locateableServices[] = new Reference($id);

                if (array_key_exists('alias', $attributes)) {
                    if (empty($attributes['alias']) || !is_string($attributes['alias'])) {
                        throw new InvalidArgumentException(
                            sprintf('Service "%s" tagged "overblog_graphql.service" should have a valid "alias" attribute.', $id)
                        );
                    }

                    $expressionLanguageDefinition->addMethodCall(
                        'addExpressionVariableNameServiceId',
                        [
                            $attributes['alias'],
                            $id,
                        ]
                    );
                }
            }
        }
        $locateableServices[] = new Reference('service_container');
        $expressionLanguageDefinition->addMethodCall(
            'addExpressionVariableNameServiceId',
            ['container', 'service_container']
        );

        $container->findDefinition(GraphQLServices::class)->addArgument(array_unique($locateableServices));
    }
}
