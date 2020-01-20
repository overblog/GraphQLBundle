<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasRoleTest extends TestCase
{
    protected function getFunctions()
    {
        $Security = $this->getSecurityIsGrantedWithExpectation(
            'ROLE_USER',
            $this->any()
        );

        return [new HasRole($Security)];
    }

    public function testEvaluator(): void
    {
        $hasRole = $this->expressionLanguage->evaluate('hasRole("ROLE_USER")');
        $this->assertTrue($hasRole);
    }

    public function testHasRole(): void
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }
}
