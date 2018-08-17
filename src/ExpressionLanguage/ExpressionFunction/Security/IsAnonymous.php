<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsAnonymous extends ExpressionFunction
{
    public function __construct($name = 'isAnonymous')
    {
        parent::__construct(
            $name,
            function () {
                return '$globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted(\'IS_AUTHENTICATED_ANONYMOUSLY\')';
            }
        );
    }
}
