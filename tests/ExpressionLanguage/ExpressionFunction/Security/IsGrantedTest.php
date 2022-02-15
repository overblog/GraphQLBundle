<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsGranted;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

final class IsGrantedTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsGranted()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            $this->matchesRegularExpression('/^ROLE_(USER|ADMIN)$/'),
            $this->any()
        );
        $gqlServices = $this->createGraphQLServices([Security::class => $security]);

        $this->assertTrue(
            $this->expressionLanguage->evaluate('isGranted("ROLE_USER")', [TypeGenerator::GRAPHQL_SERVICES => $gqlServices])
        );
    }

    public function testIsGranted(): void
    {
        $this->assertExpressionCompile('isGranted("ROLE_ADMIN")', 'ROLE_ADMIN');
    }
}
