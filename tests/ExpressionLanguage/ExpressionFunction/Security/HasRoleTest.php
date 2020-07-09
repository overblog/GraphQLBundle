<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasRoleTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasRole()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            'ROLE_USER',
            $this->any()
        );
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['security' => $security]);

        $hasRole = $this->expressionLanguage->evaluate('hasRole("ROLE_USER")', [TypeGenerator::GLOBAL_VARS => ${TypeGenerator::GLOBAL_VARS}]);
        $this->assertTrue($hasRole);
    }

    public function testHasRole(): void
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }
}
