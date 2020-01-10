<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsFullyAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsFullyAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = parent::getSecurityIsGrantedWithExpectation(
            'IS_AUTHENTICATED_FULLY',
            $this->any()
        );

        return [new IsFullyAuthenticated($authorizationChecker)];
    }

    public function testEvaluator(): void
    {
        $isFullyAuthenticated = $this->expressionLanguage->evaluate('isFullyAuthenticated()');
        $this->assertTrue($isFullyAuthenticated);
    }

    public function testIsFullyAuthenticated(): void
    {
        $this->assertExpressionCompile('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }
}
