<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
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
        $this->tagAllNotTaggedGraphQLServices($container);

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

    private function tagAllNotTaggedGraphQLServices(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('overblog_graphql_types.config')) {
            return;
        }
        /** @var array $configs */
        $configs = $container->getParameter('overblog_graphql_types.config');
        foreach ($configs as &$typeConfig) {
            switch ($typeConfig['type']) {
                case 'object':
                    if (isset($typeConfig['config']['fieldResolver'])) {
                        $this->resolveServiceIdAndMethod($container, $typeConfig['config']['fieldResolver']);
                    }

                    foreach ($typeConfig['config']['fields'] as &$field) {
                        if (isset($field['resolver'])) {
                            $this->resolveServiceIdAndMethod($container, $field['resolver']);
                        }
                    }
                    break;

                case 'interface':
                case 'union':
                    if (isset($typeConfig['config']['typeResolver'])) {
                        $this->resolveServiceIdAndMethod($container, $typeConfig['config']['typeResolver']);
                    }
                break;
            }
        }
        $container->setParameter('overblog_graphql_types.config', $configs);
    }

    private function resolveServiceIdAndMethod(ContainerBuilder $container, ?array &$resolver): void
    {
        if (!isset($resolver['id']) && !isset($resolver['method'])) {
            return;
        }
        $originalId = $resolver['id'] ?? null;
        $originalMethod = $resolver['method'] ?? null;

        if (null === $originalId) {
            [$id, $method] = explode('::', $originalMethod, 2) + [null, null];
            $throw = false;
        } else {
            $id = $originalId;
            $method = $originalMethod;
            $throw = true;
        }

        try {
            $definition = $container->getDefinition($id);
        } catch (ServiceNotFoundException $e) {
            // get Alias real service ID
            try {
                $alias = $container->getAlias($id);
                $id = (string) $alias;
                $definition = $container->getDefinition($id);
            } catch (ServiceNotFoundException | InvalidArgumentException $e) {
                if ($throw) {
                    throw $e;
                }
                $resolver['id'] = null;
                $resolver['method'] = $originalMethod;

                return;
            }
        }
        if (
            !$definition->hasTag('overblog_graphql.service')
            && !$definition->hasTag('overblog_graphql.global_variable')
        ) {
            $definition->addTag('overblog_graphql.service');
        }

        $resolver['id'] = $id;
        $resolver['method'] = $method;
    }
}
