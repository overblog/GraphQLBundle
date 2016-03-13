<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Resolver\ConfigResolver;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;

class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    use DIContainerMockTrait;

    /** @var  ConfigResolver */
    private $configResolver;

    public function setUp()
    {
        $container = $this->getDIContainerMock();
        $container
            ->method('get')
            ->will($this->returnValue(new \stdClass()));

        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->setContainer($container);

        $typeResolver = $this->getMockBuilder('Overblog\GraphQLBundle\Resolver\TypeResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $typeResolver
            ->method('resolve')
            ->will($this->returnValue(new \stdClass()));

        $fieldResolver = $this->getMockBuilder('Overblog\GraphQLBundle\Resolver\FieldResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldResolver
            ->method('resolve')
            ->will($this->returnValue(new \stdClass()));

        $argResolver = $this->getMockBuilder('Overblog\GraphQLBundle\Resolver\ArgResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $argResolver
            ->method('resolve')
            ->will($this->returnValue(new \stdClass()));

        $this->configResolver = new ConfigResolver(
            $typeResolver,
            $fieldResolver,
            $argResolver,
            $expressionLanguage,
            true
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config must be an array or implement \ArrayAccess interface
     */
    public function testConfigNotArrayOrImplementArrayAccess()
    {
        $this->configResolver->resolve('Not Array');
    }

    public function testResolveValues()
    {
        $config = $this->configResolver->resolve(
            [
                'values' => [
                    'test' => ['value' => 'my test value'],
                    'toto' => ['value' => 'my toto value'],
                    'expression-language-test' => ['value' => '@=["my", "test"]'],
                ],
            ]
        );

        $expected = [
            'values' => [
                'test' => ['value' => 'my test value'],
                'toto' => ['value' => 'my toto value'],
                'expression-language-test' => ['value' => ['my', 'test']],
            ],
        ];

        $this->assertEquals($expected, $config);
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Error\UserError
     * @expectedExceptionMessage Access denied to this field
     */
    public function testResolveAccessAndWrapResolveCallbackWithScalarValueAndAccessDenied()
    {
        $callback = $this->invokeResolveAccessAndWrapResolveCallback(false);
        $callback('toto');
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Error\UserError
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
            $this->configResolver,
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
