<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAnonymous;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

final class IsAnonymousTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsAnonymous()];
    }

    public function testEvaluator(): void
    {
        $security = $this->getSecurityIsGrantedWithExpectation('PUBLIC_ACCESS', $this->any());
        $services = $this->createGraphQLServices(['security' => $security]);

        $isAnonymous = $this->expressionLanguage->evaluate('isAnonymous()', [TypeGenerator::GRAPHQL_SERVICES => $services]);
        $this->assertTrue($isAnonymous);
    }

    public function testIsAnonymous(): void
    {
        $this->assertExpressionCompile('isAnonymous()', 'PUBLIC_ACCESS');
    }
}
