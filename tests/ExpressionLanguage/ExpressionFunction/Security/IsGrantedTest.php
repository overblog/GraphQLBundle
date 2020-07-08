<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsGranted;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

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
        $globalVariable = new GlobalVariables(['security' => $security]);

        $this->assertTrue(
            $this->expressionLanguage->evaluate('isGranted("ROLE_USER")', ['globalVariable' => $globalVariable])
        );
    }

    public function testIsGranted(): void
    {
        $this->assertExpressionCompile('isGranted("ROLE_ADMIN")', 'ROLE_ADMIN');
    }
}
