<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HasRoleTest extends TestCase
{
    protected function getFunctions()
    {
        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            'ROLE_USER',
            $this->any()
        );

        return [new HasRole($authorizationChecker)];
    }

    public function testEvaluator()
    {
        $hasRole = $this->expressionLanguage->evaluate('hasRole("ROLE_USER")');
        $this->assertTrue($hasRole);
    }

    public function testHasRole(): void
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }
}
