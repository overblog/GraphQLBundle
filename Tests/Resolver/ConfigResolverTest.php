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
use Overblog\GraphQLBundle\Resolver\ConfigResolver;

class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigResolver */
    private static $configResolver;

    public function setUp()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->getMock();
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

        self::$configResolver = new ConfigResolver(
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
        self::$configResolver->resolve('Not Array');
    }

    public function testResolveValues()
    {
        $config = self::$configResolver->resolve(
            [
                'values' => [
                    'test'                     => ['value' => 'my test value'],
                    'toto'                     => ['value' => 'my toto value'],
                    'expression-language-test' => ['value' => '@=["my", "test"]'],
                ],
            ]
        );

        $expected = [
            'values' => [
                'test'                     => ['value' => 'my test value'],
                'toto'                     => ['value' => 'my toto value'],
                'expression-language-test' => ['value' => ['my', 'test']],
            ],
        ];

        $this->assertEquals($expected, $config);
    }
}
