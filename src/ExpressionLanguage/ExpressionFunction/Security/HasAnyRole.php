<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyRole',
            fn ($roles) => "$this->globalVars->get('security')->hasAnyRole($roles)",
            static fn (array $arguments, $roles) => $arguments[TypeGenerator::GLOBAL_VARS]->get('security')->hasAnyRole($roles)
        );
    }
}
