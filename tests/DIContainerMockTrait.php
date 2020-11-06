<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests;

use PHPUnit_Framework_MockObject_MockBuilder;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class DIContainerMockTrait.
 *
 * @method PHPUnit_Framework_MockObject_MockBuilder getMockBuilder (string $className)
 */
trait DIContainerMockTrait
{
    /**
     * @return \Psr\Container\ContainerInterface&\Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getDIContainerMock(array $services = [], array $parameters = [])
    {
        $container = $this->getMockBuilder(Container::class)
            ->setMethods(['get', 'getParameter', 'has'])
            ->getMock();

        $getMethod = $container->expects($this->any())->method('get');
        $hasMethod = $container->expects($this->any())->method('has');

        foreach ($services as $id => $service) {
            $getMethod->with($id)->willReturn($service);
            $hasMethod->with($id)->willReturn(true);
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
