<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyPermission;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use stdClass;

final class HasAnyPermissionTest extends TestCase
{
    private string $testedExpression = 'hasAnyPermission(object,["OWNER", "WRITER"])';

    protected function getFunctions()
    {
        return [new HasAnyPermission()];
    }

    public function testEvaluator(): void
    {
        $expectedObject = new stdClass();
        $security = $this->getSecurityIsGrantedWithExpectation(
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($expectedObject),
            ],
            $this->any()
        );
        $services = $this->createGraphQLServices([Security::class => $security]);

        $hasPermission = $this->expressionLanguage->evaluate(
            $this->testedExpression,
            [
                TypeGenerator::GRAPHQL_SERVICES => $services,
                'object' => $expectedObject,
            ]
        );
        $this->assertTrue($hasPermission);
    }

    public function testHasAnyPermission(): void
    {
        $expectedObject = new stdClass();

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
