<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsRememberMe;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsRememberMeTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            'IS_AUTHENTICATED_REMEMBERED',
            $this->any()
        );

        return [new IsRememberMe($authorizationChecker)];
    }

    public function testEvaluator(): void
    {
        $isRememberMe = $this->expressionLanguage->evaluate('isRememberMe()');
        $this->assertTrue($isRememberMe);
    }

    public function testIsRememberMe(): void
    {
        $this->assertExpressionCompile('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }
}
