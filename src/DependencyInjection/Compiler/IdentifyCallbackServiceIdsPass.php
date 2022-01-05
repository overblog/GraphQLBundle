<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class IdentifyCallbackServiceIdsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
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

    private function resolveServiceIdAndMethod(ContainerBuilder $container, ?array &$callback): void
    {
        if (!isset($callback['function'])) {
            return;
        }
        [$id, $method] = explode('::', $callback['function'], 2) + [null, null];
        if (str_starts_with($id, '\\')) {
            $id = ltrim($id, '\\');
        }

        try {
            $definition = $container->getDefinition($id);
        } catch (ServiceNotFoundException $e) {
            // get Alias real service ID
            try {
                $alias = $container->getAlias($id);
                $id = (string) $alias;
                $definition = $container->getDefinition($id);
            } catch (ServiceNotFoundException|InvalidArgumentException $e) {
                return;
            }
        }
        if (
            !$definition->hasTag('overblog_graphql.service')
            && !$definition->hasTag('overblog_graphql.global_variable')
        ) {
            $definition->addTag('overblog_graphql.service');
        }

        $callback['function'] = "$id::$method";
    }
}
