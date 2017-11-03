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

use GraphQL\Executor\Promise\PromiseAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AutowiringTypesPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        version_compare(Kernel::VERSION, '3.3.0', '>=') ?
            $container->setAlias(PromiseAdapter::class, 'overblog_graphql.promise_adapter') :
            $container->findDefinition('overblog_graphql.promise_adapter')->setAutowiringTypes([PromiseAdapter::class])
        ;
    }
}
