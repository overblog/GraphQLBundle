<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IsAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isAuthenticated',
            fn () => "$this->globalVars->get('security')->isAuthenticated()",
            static fn (array $arguments) => $arguments[TypeGenerator::GLOBAL_VARS]->get('security')->isAuthenticated()
        );
    }
}
