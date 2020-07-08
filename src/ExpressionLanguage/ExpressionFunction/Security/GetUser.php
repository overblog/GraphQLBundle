<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class GetUser extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getUser',
            static fn () => "$this->globalVars->get('security')->getUser()",
            static fn (array $arguments) => $arguments['globalVariables']->get('security')->getUser()
        );
    }
}
