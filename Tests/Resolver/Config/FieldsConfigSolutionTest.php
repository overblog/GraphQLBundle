<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver\Config;

use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Resolver\Config\FieldsConfigSolution;

/**
 * @property FieldsConfigSolution $configSolution
 */
class FieldsConfigSolutionTest extends AbstractConfigSolutionTest
{
    protected function createConfigSolution()
    {
        $typeConfigSolution = $this->getMockBuilder('Overblog\GraphQLBundle\Resolver\Config\TypeConfigSolution')->getMock();
        $resolveCallbackConfigSolution = $this->getMockBuilder('Overblog\GraphQLBundle\Resolver\Config\ResolveCallbackConfigSolution')->getMock();

        return new FieldsConfigSolution($typeConfigSolution, $resolveCallbackConfigSolution);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Error\UserWarning
     * @expectedExceptionMessage Access denied to this field
     */
    public function testResolveAccessAndWrapResolveCallbackWithScalarValueAndAccessDenied()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback(false);
        $callback('toto');
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Error\UserWarning
     * @expectedExceptionMessage Access denied to this field
     */
    public function testResolveAccessAndWrapResolveCallbackWithScalarValueAndExpressionEvalThrowingException()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback('@=oups');
        $callback('titi');
    }

    public function testResolveAccessAndWrapResolveCallbackWithScalarValueAndAccessDeniedGranted()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback(true);
        $this->assertEquals('toto', $callback('toto'));
    }

    public function testResolveAccessAndWrapResolveCallbackWithArrayAndAccessDeniedToEveryItemStartingByTo()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback('@=not(object matches "/^to.*/i")');
        $this->assertEquals(
            [
                'tata',
                'titi',
                'tata',
                null,
                null,
            ],
            $callback(
                [
                    'tata',
                    'titi',
                    'tata',
                    'toto',
                    'tota',
                ]
            )
        );
    }

    public function testResolveAccessAndWrapResolveCallbackWithRelayConnectionAndAccessGrantedToEveryNodeStartingByTo()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback('@=object matches "/^to.*/i"');
        $this->assertEquals(
            ConnectionBuilder::connectionFromArray(['toto', 'toti', null, null, null]),
            $callback(
                ConnectionBuilder::connectionFromArray(['toto', 'toti', 'coco', 'foo', 'bar'])
            )
        );
    }

    /**
     * @param bool|string   $hasAccess
     * @param callable|null $callback
     *
     * @return callback
     */
    private function invokeResolveAccessAndWrapResolveCallback($hasAccess, callable $callback = null)
    {
        if (null === $callback) {
            $callback = function ($value) {
                return $value;
            };
        }

        return $this->invokeMethod(
            $this->configSolution,
            'resolveAccessAndWrapResolveCallback',
            [
                $hasAccess,
                $callback,
            ]
        );
    }

    /**
     * Call protected/private method of a class.
     *
     * @see https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     *
     * @param object $object     Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
