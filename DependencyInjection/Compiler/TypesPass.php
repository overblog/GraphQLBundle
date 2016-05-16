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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

class TypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('overblog_graphql_types.config');
        $classes = array_keys($container->get('overblog_graphql.cache_compiler')->compile($this->processConfig($config)));

        foreach ($classes as $class) {
            $name = $class::getName();

            $customTypeId = sprintf('overblog_graphql.definition.custom_%s_type', $container->underscore($name));

            $container
                ->setDefinition($customTypeId, new Definition($class))
                ->setArguments([new Reference('service_container')])
                ->addTag('overblog_graphql.type', ['alias' => $name])
            ;
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
