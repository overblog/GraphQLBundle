<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsAuthenticated()];
    }

    public function testEvaluator(): void
    {
        $security       = $this->getSecurityIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'),
            $this->any()
        );
        $globalVariable = new GlobalVariables(['security' => $security]);

        $isAuthenticated = $this->expressionLanguage->evaluate(
            'isAuthenticated()',
            ['globalVariable' => $globalVariable]
        );
        $this->assertTrue($isAuthenticated);
    }

    public function testIsAuthenticated(): void
    {
        $this->assertExpressionCompile(
            'isAuthenticated()',
            $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/')
        );
    }
}
