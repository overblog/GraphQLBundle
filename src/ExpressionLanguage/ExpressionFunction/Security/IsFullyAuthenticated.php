<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct($name = 'isFullyAuthenticated')
    {
        parent::__construct(
            $name,
            function () {
                return '$container->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_FULLY\')';
            }
        );
    }
}
