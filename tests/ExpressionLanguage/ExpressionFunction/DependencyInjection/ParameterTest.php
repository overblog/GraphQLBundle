<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
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
    public function testParameter($name): void
    {
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock([], ['test' => 5])]);
        $globalVariable->get('container');
        $this->assertSame(5, eval('return '.$this->expressionLanguage->compile($name.'("test")').';'));
    }

    public function getNames()
    {
        return [
            ['param'],
            ['parameter'],
        ];
    }
}
