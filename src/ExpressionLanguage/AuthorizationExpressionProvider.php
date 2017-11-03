<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyPermission;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyRole;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasPermission;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasRole;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAnonymous;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsAuthenticated;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsFullyAuthenticated;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\IsRememberMe;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class AuthorizationExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new HasRole(),
            new HasAnyRole(),
            new IsAnonymous(),
            new IsRememberMe(),
            new IsFullyAuthenticated(),
            new IsAuthenticated(),
            new HasPermission(),
            new HasAnyPermission(),
        ];
    }
}
