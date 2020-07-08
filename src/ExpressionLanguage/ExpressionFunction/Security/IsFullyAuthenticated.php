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
            static fn () => "$this->globalVars->get('security')->isFullyAuthenticated()",
            static fn (array $arguments) => $arguments['globalVariables']->isFullyAuthenticated()
        );
    }
}
