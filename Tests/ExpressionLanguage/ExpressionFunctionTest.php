<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use PHPUnit\Framework\TestCase;

class ExpressionFunctionTest extends TestCase
{
    public function testFunctionDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PHP function "fn_does_not_exist" does not exist.');

        ExpressionFunction::fromPhp('fn_does_not_exist');
    }

    public function testThereIsNoEvaluator()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluator is not needed');

        (new ExpressionFunction('name', function () {}))->getEvaluator()();
    }
}
