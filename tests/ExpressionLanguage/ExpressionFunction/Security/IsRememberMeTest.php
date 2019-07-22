<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsRememberMe;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class IsRememberMeTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();

        return [new IsRememberMe($authorizationChecker)];
    }

    public function testIsRememberMe(): void
    {
        $this->assertExpressionCompile('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }
}
