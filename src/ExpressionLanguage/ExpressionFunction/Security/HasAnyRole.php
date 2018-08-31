<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct($name = 'hasAnyRole')
    {
        parent::__construct(
            $name,
            function ($roles) {
                $code = \sprintf('array_reduce(%s, function ($isGranted, $role) use (%s) { return $isGranted || $globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted($role); }, false)', $roles, TypeGenerator::USE_FOR_CLOSURES);

                return $code;
            }
        );
    }
}
