<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct($name = 'hasAnyPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permissions) {
                $code = sprintf('array_reduce(%s, function ($isGranted, $permission) use ($container, $object) { return $isGranted || $container->get(\'security.authorization_checker\')->isGranted($permission, %s); }, false)', $permissions, $object);

                return $code;
            }
        );
    }
}
