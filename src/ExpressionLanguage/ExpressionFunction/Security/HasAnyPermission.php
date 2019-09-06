<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, $name = 'hasAnyPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permissions) {
                $code = \sprintf('array_reduce(%s, function ($isGranted, $permission) use (%s, $object) { return $isGranted || $globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, TypeGenerator::USE_FOR_CLOSURES, $object);

                return $code;
            },
            function ($_, $object, $permissions) use ($authorizationChecker) {
                return array_reduce(
                    $permissions,
                    function ($isGranted, $permission) use ($authorizationChecker, $object) { return $isGranted || $authorizationChecker->isGranted($permission, $object); },
                    false
                );
            }
        );
    }
}
