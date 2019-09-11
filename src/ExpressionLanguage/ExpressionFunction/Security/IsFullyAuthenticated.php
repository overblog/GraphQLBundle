<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct(
            'isFullyAuthenticated',
            function () {
                return "\$globalVariable->get('container')->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')";
            },
            function () use ($authorizationChecker) {
                return $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY');
            }
        );
    }
}
