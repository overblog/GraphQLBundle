<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasAnyPermission',
            fn ($object, $permissions) => "$this->globalVars->get('security')->hasAnyPermission($object, $permissions)",
            fn ($_, $object, $permissions) => $security->hasAnyPermission($object, $permissions)
        );
    }
}
