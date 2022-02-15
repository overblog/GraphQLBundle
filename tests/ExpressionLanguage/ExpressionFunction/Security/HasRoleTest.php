<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

final class HasRoleTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasRole()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation(
            'ROLE_USER',
            $this->any()
        );
        $services = $this->createGraphQLServices([Security::class => $security]);

        $hasRole = $this->expressionLanguage->evaluate('hasRole("ROLE_USER")', [TypeGenerator::GRAPHQL_SERVICES => $services]);
        $this->assertTrue($hasRole);
    }

    public function testHasRole(): void
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }
}
