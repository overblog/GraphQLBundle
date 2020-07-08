<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsAnonymous extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isAnonymous',
            fn () => "$this->globalVars->get('security')->isAnonymous()",
            static fn (array $arguments) => $arguments['globalVariables']->get('security')->isAnonymous()
        );
    }
}
