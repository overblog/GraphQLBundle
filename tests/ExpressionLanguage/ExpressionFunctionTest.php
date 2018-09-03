<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use PHPUnit\Framework\TestCase as BaseTestCase;

class ExpressionFunctionTest extends BaseTestCase
{
    public function testThereIsNoEvaluator(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Evaluator is not needed');

        $expressionFunction = new ExpressionFunction('name', function (): void {
        });
        $evaluator = $expressionFunction->getEvaluator();

        $this->assertTrue(\is_callable($evaluator));

        $evaluator();
    }
}
