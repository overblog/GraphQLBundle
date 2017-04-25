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
