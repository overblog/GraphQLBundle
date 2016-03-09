<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests;

trait DIContainerMockTrait
{
    private function getDIContainerMock(array $services = [], array $parameters = [])
    {
        $container = $this->getMock('Symfony\\Component\\DependencyInjection\\Container', ['get', 'getParameter', 'has']);

        $getMethod = $container->expects($this->any())->method('get');

        foreach ($services as $id => $service) {
            $getMethod
                ->with($id)
                ->willReturn($service)
            ;
        }

        $getParameterMethod = $container->expects($this->any())->method('getParameter');

        foreach ($parameters as $id => $parameter) {
            $getParameterMethod
                ->with($id)
                ->willReturn($parameter)
            ;
        }

        return $container;
    }
}
