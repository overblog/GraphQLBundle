<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HasPermission extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, $name = 'hasPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permission) {
                return "\$globalVariable->get('container')->get('security.authorization_checker')->isGranted($permission, $object)";
            },
            function ($_, $object, $permission) use ($authorizationChecker) {
                return $authorizationChecker->isGranted($permission, $object);
            }
        );
    }
}
