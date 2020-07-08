<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasPermission',
            fn ($object, $permission) => "$this->globalVars->get('security')->hasPermission($object, $permission)",
            static fn (array $arguments, $object, $permission) => $arguments['globalVariables']->get('security')->hasPermission($object, $permission)
        );
    }
}
