<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasPermission extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasPermission',
            fn ($object, $permission) => "$this->globalVars->get('security')->hasPermission($object, $permission)",
            fn ($_, $object, $permission) => $security->hasPermission($object, $permission)
        );
    }
}
