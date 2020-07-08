<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasRole',
            static fn ($role) => "$this->globalVars->get('security')->hasRole($role)",
            static fn (array $arguments, $role) => $arguments['globalVariables']->get('security')->hasRole($role)
        );
    }
}
