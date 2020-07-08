<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isAuthenticated',
            fn () => "$this->globalVars->get('security')->isAuthenticated()",
            static fn (array $arguments) => $arguments['globalVariables']->get('security')->isAuthenticated()
        );
    }
}
