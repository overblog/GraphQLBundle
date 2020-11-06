<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Parameter;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class ParameterTest extends TestCase
{
    protected function getFunctions()
    {
        return [
            new Parameter(),
            new Parameter('param'),
        ];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testParameterCompilation($name): void
    {
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['container' => $this->getDIContainerMock([], ['test' => 5])]);
        ${TypeGenerator::GLOBAL_VARS}->get('container');
        $this->assertSame(5, eval('return '.$this->expressionLanguage->compile($name.'("test")').';'));
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testParameterEvaluation($name): void
    {
        $globalVars = new GlobalVariables(['container' => $this->getDIContainerMock([], ['test' => 5])]);
        $this->assertSame(
            5,
            $this->expressionLanguage->evaluate($name.'("test")', [TypeGenerator::GLOBAL_VARS => $globalVars])
        );
    }

    public function getNames(): array
    {
        return [
            ['param'],
            ['parameter'],
        ];
    }
}
