<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasAnyPermissionTest extends TestCase
{
    private $testedExpression = 'hasAnyPermission(object,["OWNER", "WRITER"])';

    protected function getFunctions()
    {
        return [new HasAnyPermission()];
    }

    public function testEvaluator(): void
    {
        $expectedObject = new \stdClass();
        $security       = $this->getSecurityIsGrantedWithExpectation(
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($expectedObject),
            ],
            $this->any()
        );
        $globalVariable = new GlobalVariables(['security' => $security]);

        $hasPermission = $this->expressionLanguage->evaluate(
            $this->testedExpression,
            [
                'globalVariable' => $globalVariable,
                'object'         => $expectedObject,
            ]
        );
        $this->assertTrue($hasPermission);
    }

    public function testHasAnyPermission(): void
    {
        $expectedObject = new \stdClass();

        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($expectedObject),
            ],
            [
                'object' => $expectedObject,
            ]
        );

        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($expectedObject),
            ],
            [
                'object' => $expectedObject,
            ],
            $this->exactly(2),
            false,
            'assertFalse'
        );
    }
}
