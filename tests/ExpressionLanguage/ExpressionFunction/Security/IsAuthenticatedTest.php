<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class IsAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'),
            $this->any()
        );

        return [new IsAuthenticated($authorizationChecker)];
    }

    public function testEvaluator()
    {
        $isAuthenticated = $this->expressionLanguage->evaluate("isAuthenticated()");
        $this->assertTrue($isAuthenticated);
    }

    public function testIsAuthenticated(): void
    {
        $this->assertExpressionCompile('isAuthenticated()', $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'));
    }
}
