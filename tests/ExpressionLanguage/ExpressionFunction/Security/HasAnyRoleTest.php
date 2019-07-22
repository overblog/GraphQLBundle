<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyRole;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HasAnyRoleTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();

        return [new HasAnyRole($authorizationChecker)];
    }

    public function testHasAnyRole(): void
    {
        $this->assertExpressionCompile('hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])', 'ROLE_ADMIN');

        $this->assertExpressionCompile(
            'hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])',
            $this->matchesRegularExpression('/^ROLE_(USER|ADMIN)$/'),
            [],
            $this->exactly(2),
            false,
            'assertFalse'
        );
    }
}
