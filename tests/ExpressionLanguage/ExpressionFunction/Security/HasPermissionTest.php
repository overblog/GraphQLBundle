<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasPermission;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use stdClass;

final class HasPermissionTest extends TestCase
{
    private string $testedExpression = 'hasPermission(object,"OWNER")';

    protected function getFunctions()
    {
        return [new HasPermission()];
    }

    public function testEvaluator(): void
    {
        $expectedObject = new stdClass();
        $security = $this->getSecurityIsGrantedWithExpectation(
            [
                'OWNER',
                $this->identicalTo($expectedObject),
            ],
            $this->any()
        );
        $services = $this->createGraphQLServices(['security' => $security]);

        $hasPermission = $this->expressionLanguage->evaluate(
            $this->testedExpression,
            [
                TypeGenerator::GRAPHQL_SERVICES => $services,
                'object' => $expectedObject,
            ]
        );
        $this->assertTrue($hasPermission);
    }

    public function testHasPermission(): void
    {
        $expectedObject = new stdClass();
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
