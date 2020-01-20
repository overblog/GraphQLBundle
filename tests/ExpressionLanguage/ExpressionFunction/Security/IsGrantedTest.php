<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsGranted;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsGrantedTest extends TestCase
{
    protected function getFunctions()
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^ROLE_(USER|ADMIN)$/'),
            $this->any()
        );

        return [new IsGranted($security)];
    }

    public function testEvaluator(): void
    {
        $this->assertTrue($this->expressionLanguage->evaluate('isGranted("ROLE_USER")'));
    }

    public function testIsGranted(): void
    {
        $this->assertExpressionCompile('isGranted("ROLE_ADMIN")', 'ROLE_ADMIN');
    }
}
