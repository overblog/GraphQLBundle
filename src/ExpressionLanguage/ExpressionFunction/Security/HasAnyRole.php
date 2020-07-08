<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyRole',
            fn ($roles) => "$this->globalVars->get('security')->hasAnyRole($roles)",
            static fn (array $arguments, $roles) => $arguments['globalVariables']->get('security')->hasAnyRole($roles)
        );
    }
}
