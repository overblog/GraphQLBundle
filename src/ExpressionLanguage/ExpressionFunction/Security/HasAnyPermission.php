<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyPermission',
            fn ($object, $permissions) => "$this->globalVars->get('security')->hasAnyPermission($object, $permissions)",
            static fn (array $arguments, $object, $permissions) => $arguments['globalVariables']->get('security')->hasAnyPermission($object, $permissions)
        );
    }
}
