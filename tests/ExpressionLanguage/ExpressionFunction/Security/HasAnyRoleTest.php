<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyRole;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasAnyRoleTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasAnyRole()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation('ROLE_ADMIN', $this->any());
        $globalVars = new GlobalVariables(['security' => $security]);

        $hasRole = $this->expressionLanguage->evaluate(
            'hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])',
            [TypeGenerator::GLOBAL_VARS => $globalVars]
        );
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
