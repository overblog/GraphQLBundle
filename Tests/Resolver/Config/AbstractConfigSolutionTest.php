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

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Resolver\Config\AbstractConfigSolution;
use Overblog\GraphQLBundle\Resolver\ConfigResolver;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;

abstract class AbstractConfigSolutionTest extends \PHPUnit_Framework_TestCase
{
    use DIContainerMockTrait;

    /**
     * @var AbstractConfigSolution
     */
    protected $configSolution;

    /**
     * @return AbstractConfigSolution
     */
    abstract protected function createConfigSolution();

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

        $this->configSolution = $this->createConfigSolution();

        $this->configSolution->setConfigResolver(new ConfigResolver())
            ->setArgResolver($argResolver)
            ->setFieldResolver($fieldResolver)
            ->setTypeResolver($typeResolver)
            ->setExpressionLanguage($expressionLanguage);
    }
}
