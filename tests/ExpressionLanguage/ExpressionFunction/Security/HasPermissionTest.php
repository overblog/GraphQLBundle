<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasPermissionTest extends TestCase
{
    private $testedExpression = 'hasPermission(object,"OWNER")';

    protected function getFunctions()
    {
        return [new HasPermission()];
    }

    public function testEvaluator(): void
    {
        $expectedObject = new \stdClass();
        $security = $this->getSecurityIsGrantedWithExpectation(
            [
                'OWNER',
                $this->identicalTo($expectedObject),
            ],
            $this->any()
        );
        $globalVariable = new GlobalVariables(['security' => $security]);

        $hasPermission = $this->expressionLanguage->evaluate(
            $this->testedExpression,
            [
                'globalVariable' => $globalVariable,
                'object' => $expectedObject,
            ]
        );
        $this->assertTrue($hasPermission);
    }

    public function testHasPermission(): void
    {
        $expectedObject = new \stdClass();
        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                'OWNER',
                $this->identicalTo($expectedObject),
            ],
            [
                'object' => $expectedObject,
            ]
        );
    }
}
