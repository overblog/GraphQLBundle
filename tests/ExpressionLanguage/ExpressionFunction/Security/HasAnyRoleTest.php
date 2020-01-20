<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyRole;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasAnyRoleTest extends TestCase
{
    protected function getFunctions()
    {
        $Security = $this->getSecurityIsGrantedWithExpectation(
            'ROLE_ADMIN',
            $this->any()
        );

        return [new HasAnyRole($Security)];
    }

    public function testEvaluator(): void
    {
        $hasRole = $this->expressionLanguage->evaluate('hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])');
        $this->assertTrue($hasRole);
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
