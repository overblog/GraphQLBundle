<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsRememberMe;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsRememberMeTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsRememberMe()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            'IS_AUTHENTICATED_REMEMBERED',
            $this->any()
        );
        $gqlServices = $this->createGraphQLServices(['security' => $security]);

        $isRememberMe = $this->expressionLanguage->evaluate('isRememberMe()', [TypeGenerator::GRAPHQL_SERVICES => $gqlServices]);
        $this->assertTrue($isRememberMe);
    }

    public function testIsRememberMe(): void
    {
        $this->assertExpressionCompile('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }
}
