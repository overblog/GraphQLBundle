<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isFullyAuthenticated',
            fn () => "$this->globalVars->get('security')->isFullyAuthenticated()",
            fn (array $arguments) => $arguments['globalVariables']->get('security')->isFullyAuthenticated()
        );
    }
}
