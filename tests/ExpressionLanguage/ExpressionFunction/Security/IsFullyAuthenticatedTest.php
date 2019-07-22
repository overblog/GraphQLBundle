<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsFullyAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class IsFullyAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();

        return [new IsFullyAuthenticated($authorizationChecker)];
    }

    public function testIsFullyAuthenticated(): void
    {
        $this->assertExpressionCompile('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }
}
