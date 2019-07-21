<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct(AuthorizationChecker $authorizationChecker, $name = 'hasAnyPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permissions) {
                $code = \sprintf('array_reduce(%s, function ($isGranted, $permission) use (%s, $object) { return $isGranted || $globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, TypeGenerator::USE_FOR_CLOSURES, $object);

                return $code;
            },
            function ($arguments, $object, $permissions) use ($authorizationChecker) {
                return $authorizationChecker->isGranted($permissions, $object);
            }
        );
    }
}
