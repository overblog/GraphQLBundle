<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use PHPUnit\Framework\TestCase;

class ExpressionFunctionTest extends TestCase
{
    public function testThereIsNoEvaluator()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluator is not needed');

        $expressionFunction = new ExpressionFunction('name', function () {
        });
        $evaluator = $expressionFunction->getEvaluator();

        $this->assertTrue(is_callable($evaluator));

        $evaluator();
    }
}
