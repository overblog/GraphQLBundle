<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasRole extends ExpressionFunction
{
    public function __construct($name = 'hasRole')
    {
        parent::__construct(
            $name,
            function ($role) {
                return sprintf('$container->get(\'security.authorization_checker\')->isGranted(%s)', $role);
            }
        );
    }
}
