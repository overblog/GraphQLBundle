<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasPermission extends ExpressionFunction
{
    public function __construct($name = 'hasPermission')
    {
        parent::__construct(
            $name,
            function ($object, $permission) {
                $code = sprintf('$container->get(\'security.authorization_checker\')->isGranted(%s, %s)', $permission, $object);

                return $code;
            }
        );
    }
}
