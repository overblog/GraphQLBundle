<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasRole',
            static function ($role): string {
                return \sprintf('$globalVariable->get(\'security\')->hasRole(%s)', $role);
            },
            static function ($arguments, $role): bool {
                return $arguments['globalVariable']->get('security')->hasRole($role);
            }
        );
    }
}
