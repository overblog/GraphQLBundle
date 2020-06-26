<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasAnyRole',
            fn ($roles) => "$this->globalVars->get('security')->hasAnyRole($roles)",
            fn ($_, $roles) => $security->hasAnyRole($roles)
        );
    }
}
