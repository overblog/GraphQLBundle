<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        $Security = $this->getSecurityIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'),
            $this->any()
        );

        return [new IsAuthenticated($Security)];
    }

    public function testEvaluator(): void
    {
        $isAuthenticated = $this->expressionLanguage->evaluate('isAuthenticated()');
        $this->assertTrue($isAuthenticated);
    }

    public function testIsAuthenticated(): void
    {
        $this->assertExpressionCompile('isAuthenticated()', $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'));
    }
}
