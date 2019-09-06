<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HasPermissionTest extends TestCase
{
    private $expectedObject;
    private $testedExpression = 'hasPermission(object,"OWNER")';


    protected function getFunctions()
    {
        $this->expectedObject = new \stdClass();

        $authorizationChecker = parent::getAuthorizationCheckerIsGrantedWithExpectation(
            [
                'OWNER',
                $this->identicalTo($this->expectedObject),
            ],
            $this->any()
        );

        return [new HasPermission($authorizationChecker)];
    }

    public function testEvaluator()
    {
        $hasPermission = $this->expressionLanguage->evaluate($this->testedExpression, ['object' => $this->expectedObject]);
        $this->assertTrue($hasPermission);
    }

    public function testHasPermission(): void
    {
        $this->assertExpressionCompile(
            $this->testedExpression,
            [
                'OWNER',
                $this->identicalTo($this->expectedObject),
            ],
            [
                'object' => $this->expectedObject,
            ]
        );
    }
}
