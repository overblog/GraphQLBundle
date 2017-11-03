<?php

namespace Overblog\GraphQLBundle\Tests;

use Symfony\Component\DependencyInjection\Container;

/**
 * Class DIContainerMockTrait.
 *
 * @method \PHPUnit_Framework_MockObject_MockBuilder getMockBuilder (string $className)
 */
trait DIContainerMockTrait
{
    private function getDIContainerMock(array $services = [], array $parameters = [])
    {
        $container = $this->getMockBuilder(Container::class)
            ->setMethods(['get', 'getParameter', 'has'])
            ->getMock();

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
