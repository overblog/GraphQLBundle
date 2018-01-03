<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsRememberMe;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class IsRememberMeTest extends TestCase
{
    protected function getFunctions()
    {
        return [new IsRememberMe()];
    }

    public function testIsRememberMe()
    {
        $this->assertExpressionCompile('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }
}
