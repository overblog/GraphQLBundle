<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAnonymous;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class IsAnonymousTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            'IS_AUTHENTICATED_ANONYMOUSLY',
            $this->any()
        );

        return [new IsAnonymous($authorizationChecker)];
    }

    public function testEvaluator()
    {
        $isAnonymous = $this->expressionLanguage->evaluate("isAnonymous()");
        $this->assertTrue($isAnonymous);
    }

    public function testIsAnonymous(): void
    {
        $this->assertExpressionCompile('isAnonymous()', 'IS_AUTHENTICATED_ANONYMOUSLY');
    }
}
