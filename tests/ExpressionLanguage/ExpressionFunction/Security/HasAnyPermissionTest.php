<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HasAnyPermissionTest extends TestCase
{
    private $expectedObject;
    private $testedExpression = 'hasAnyPermission(object,["OWNER", "WRITER"])';

    protected function getFunctions()
    {
        $this->expectedObject = new \stdClass();

        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($this->expectedObject),
            ],
            $this->any()
        );

        return [new HasAnyPermission($authorizationChecker)];
    }

    public function testEvaluator()
    {
        $hasPermission = $this->expressionLanguage->evaluate($this->testedExpression, ['object' => $this->expectedObject]);
        $this->assertTrue($hasPermission);
    }

    public function testHasAnyPermission(): void
    {
        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($this->expectedObject),
            ],
            [
                'object' => $this->expectedObject,
            ]
        );

        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($this->expectedObject),
            ],
            [
                'object' => $this->expectedObject,
            ],
            $this->exactly(2),
            false,
            'assertFalse'
        );
    }
}
