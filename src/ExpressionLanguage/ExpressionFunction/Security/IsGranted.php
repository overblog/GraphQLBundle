<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class IsGranted extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        parent::__construct(
            'isGranted',
            function ($attributes, $object = null) {
                return "\$globalVariable->get('container')->get('security.authorization_checker')->isGranted($attributes, $object)";
            },
            function ($_, $attributes, $object = null) use ($authorizationChecker) {
                return $authorizationChecker->isGranted($attributes, $object);
            }
        );
    }
}
