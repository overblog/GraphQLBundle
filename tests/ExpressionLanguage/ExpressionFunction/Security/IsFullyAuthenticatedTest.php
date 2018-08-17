<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsFullyAuthenticated;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsFullyAuthenticatedTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsFullyAuthenticated()];
    }

    public function testIsFullyAuthenticated()
    {
        $this->assertExpressionCompile('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }
}
