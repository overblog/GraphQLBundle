<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct($name = 'hasAnyPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permissions) {
                $code = \sprintf('array_reduce(%s, function ($isGranted, $permission) use (%s, $object) { return $isGranted || $globalVariable->get(\'container\')->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, TypeGenerator::USE_FOR_CLOSURES, $object);

                return $code;
            }
        );
    }
}
