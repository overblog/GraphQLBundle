<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\ExpressionLanguage\Expression;

class ConfigTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('overblog_graphql_types.config');
        $generatedClasses = $container->get('overblog_graphql.cache_compiler')->compile($this->processConfig($config));

        foreach ($generatedClasses as $class => $file) {
            $aliases = call_user_func($class.'::getAliases');
            $this->setTypeServiceDefinition($container, $class, $aliases);
        }
    }

    private function setTypeServiceDefinition(ContainerBuilder $container, $class, array $aliases)
    {
        $definition = $container->setDefinition($class, new Definition($class));
        $definition->setPublic(false);
        $definition->setAutowired(true);
        foreach ($aliases as $alias) {
            $definition->addTag('overblog_graphql.type', ['alias' => $alias]);
        }
    }

    private function processConfig(array $configs)
    {
        return array_map(
            function ($v) {
                if (is_array($v)) {
                    return call_user_func([$this, 'processConfig'], $v);
                } elseif (is_string($v) && 0 === strpos($v, '@=')) {
                    return new Expression(substr($v, 2));
                }

                return $v;
            },
            $configs
        );
    }
}
