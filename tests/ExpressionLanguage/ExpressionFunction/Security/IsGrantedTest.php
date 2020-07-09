<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsGranted;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Overblog\GraphQLBundle\Tests\Generator\TypeGenerator;

class IsGrantedTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsGranted()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^ROLE_(USER|ADMIN)$/'),
            $this->any()
        );
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['security' => $security]);

        $this->assertTrue(
            $this->expressionLanguage->evaluate('isGranted("ROLE_USER")', [TypeGenerator::GLOBAL_VARS => ${TypeGenerator::GLOBAL_VARS}])
        );
    }

    public function testIsGranted(): void
    {
        $this->assertExpressionCompile('isGranted("ROLE_ADMIN")', 'ROLE_ADMIN');
    }
}
