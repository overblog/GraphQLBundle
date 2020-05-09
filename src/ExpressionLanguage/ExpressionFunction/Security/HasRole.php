<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasRole extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasRole',
            static function ($role): string {
                return \sprintf('$globalVariable->get(\'security\')->hasRole(%s)', $role);
            },
            static function ($_, $role) use ($security): bool {
                return $security->hasRole($role);
            }
        );
    }
}
