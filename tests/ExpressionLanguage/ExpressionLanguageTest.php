<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;

final class ExpressionLanguageTest extends TestCase
{
    #[DataProvider('expressionContainsVarProvider')]
    /**
     * @param Expression|string $expression
     */
    public function testExpressionContainsVar($expression, bool $expectedResult): void
    {
        $result = ExpressionLanguage::expressionContainsVar('validator', $expression);

        $this->assertSame($expectedResult, $result);
    }

    #[DataProvider('extractExpressionVarNamesProvider')]
    /**
     * @param Expression|string $expression
     */
    public function testExtractExpressionVarNames($expression, array $expectedResult): void
    {
        $result = ExpressionLanguage::extractExpressionVarNames($expression);

        $this->assertSame($expectedResult, $result);
    }

    public function expressionContainsVarProvider(): iterable
    {
        yield ["@=test('default', 15.6, validator)", true];
        yield ["@=validator('default', 15.6)", false];
        yield ["validator('default', validator(), 15.6)", false];
        yield [new Expression("validator('default', validator(), 15.6)"), false];
        yield [new Expression('validator'), true];
        yield ['toto.validator', false];
        yield ['toto . validator', false];
        yield ['toto.test && validator', true];
        yield ['toto . test && validator', true];
    }

    public function extractExpressionVarNamesProvider(): iterable
    {
        yield ['@=a + b - c', ['a', 'b', 'c']];
        yield ['f()', []];
        yield ['a.c + b', ['a', 'b']];
        yield ['(a.c) + b - d', ['a', 'b', 'd']];
        yield ['a && b && c', ['a', 'b', 'c']];
    }
}
