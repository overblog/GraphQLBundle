<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection\Parameter;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ParameterTest extends TestCase
{
    protected function getFunctions()
    {
        $parameterBag = new ParameterBag();
        $parameterBag->set('test', 5);

        return [
            new Parameter($parameterBag),
            new Parameter($parameterBag, 'param'),
        ];
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testParameterCompilation($name): void
    {
        $globalVariables = new GlobalVariables(['container' => $this->getDIContainerMock([], ['test' => 5])]);
        $globalVariables->get('container');
        $this->assertSame(5, eval('return '.$this->expressionLanguage->compile($name.'("test")').';'));
    }

    /**
     * @param string $name
     * @dataProvider getNames
     */
    public function testParameterEvaluation($name): void
    {
        $this->assertSame(5, $this->expressionLanguage->evaluate($name.'("test")'));
    }

    public function getNames()
    {
        return [
            ['param'],
            ['parameter'],
        ];
    }
}
