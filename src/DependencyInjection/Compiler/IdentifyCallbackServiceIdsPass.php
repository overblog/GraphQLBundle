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
        if (!isset($callback['id']) && !isset($callback['method'])) {
            return;
        }
        $originalId = $callback['id'] ?? null;
        $originalMethod = $callback['method'] ?? null;

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
                $callback['id'] = null;
                $callback['method'] = $originalMethod;

                return;
            }
        }
        if (
            !$definition->hasTag('overblog_graphql.service')
            && !$definition->hasTag('overblog_graphql.global_variable')
        ) {
            $definition->addTag('overblog_graphql.service');
        }

        $callback['id'] = $id;
        $callback['method'] = $method;
    }
}
