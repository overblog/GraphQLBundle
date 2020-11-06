<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Generator;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;

class ExpressionLanguageTest extends TestCase
{
    /**
     * @test
     * @dataProvider expressionProvider
     *
     * @param Expression|string $expression
     */
    public function expressionContainsVar($expression, bool $expectedResult): void
    {
        $result = ExpressionLanguage::expressionContainsVar('validator', $expression);

        $this->assertEquals($result, $expectedResult);
    }

    public function expressionProvider(): Generator
    {
        yield ["@=test('default', 15.6, validator)", true];
        yield ["@=validator('default', 15.6)", false];
        yield ["validator('default', validator(), 15.6)", false];
        yield [new Expression("validator('default', validator(), 15.6)"), false];
        yield [new Expression('validator'), true];
    }
}
