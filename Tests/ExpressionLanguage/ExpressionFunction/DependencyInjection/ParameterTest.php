<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Parameter;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ParameterTest extends TestCase
{
    protected function getFunctions()
    {
        return [new Parameter(), new Parameter('param')];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testParameter($name)
    {
        $container = $this->getDIContainerMock([], ['test' => 5]);
        $this->expressionLanguage->setContainer($container);
        $this->assertEquals(5, eval('return '.$this->expressionLanguage->compile($name.'("test")').';'));
    }

    public function getNames()
    {
        return [
            ['param'],
            ['parameter'],
        ];
    }
}
