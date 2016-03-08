<?php

namespace Overblog\GraphQLBundle\Tests;

trait DIContainerMockTrait
{
    private function getDIContainerMock(array $services = [], array $parameters = [])
    {
        $container = $this->getMock('Symfony\\Component\\DependencyInjection\\Container', ['get', 'getParameter']);

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
