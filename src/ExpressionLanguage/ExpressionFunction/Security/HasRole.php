<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HasRole extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct(
            'hasRole',
            function ($role): string {
                return "\$globalVariable->get('container')->get('security.authorization_checker')->isGranted($role)";
            },
            function ($_, $role) use ($authorizationChecker): bool {
                return $authorizationChecker->isGranted($role);
            }
        );
    }
}
