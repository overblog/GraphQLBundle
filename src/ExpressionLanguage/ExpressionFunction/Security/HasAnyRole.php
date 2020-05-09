<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasAnyRole',
            static function ($roles): string {
                return \sprintf('$globalVariable->get(\'security\')->hasAnyRole(%s)', $roles);
            },
            static function ($_, $roles) use ($security): bool {
                return $security->hasAnyRole($roles);
            }
        );
    }
}
