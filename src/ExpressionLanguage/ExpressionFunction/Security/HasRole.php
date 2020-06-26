<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasRole extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasRole',
            fn ($role) => "$this->globalVars->get('security')->hasRole($role)",
            fn ($_, $role) => $security->hasRole($role)
        );
    }
}
