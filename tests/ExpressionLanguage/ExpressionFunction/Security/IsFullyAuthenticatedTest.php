<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsFullyAuthenticated;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsFullyAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsFullyAuthenticated()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            'IS_AUTHENTICATED_FULLY',
            $this->any()
        );
        $gqlServices = new GraphQLServices(['security' => $security]);

        $isFullyAuthenticated = $this->expressionLanguage->evaluate(
            'isFullyAuthenticated()',
            [TypeGenerator::GRAPHQL_SERVICES => $gqlServices]
        );
        $this->assertTrue($isFullyAuthenticated);
    }

    public function testIsFullyAuthenticated(): void
    {
        $this->assertExpressionCompile('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }
}
