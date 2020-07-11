<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAnonymous;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsAnonymousTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsAnonymous()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            'IS_AUTHENTICATED_ANONYMOUSLY',
            $this->any()
        );
        $globalVars = new GlobalVariables(['security' => $security]);

        $isAnonymous = $this->expressionLanguage->evaluate('isAnonymous()', [TypeGenerator::GLOBAL_VARS => $globalVars]);
        $this->assertTrue($isAnonymous);
    }

    public function testIsAnonymous(): void
    {
        $this->assertExpressionCompile('isAnonymous()', 'IS_AUTHENTICATED_ANONYMOUSLY');
    }
}
