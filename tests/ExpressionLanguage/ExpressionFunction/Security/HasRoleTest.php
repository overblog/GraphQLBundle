<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasRoleTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasRole()];
    }

    public function testHasRole(): void
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }
}
