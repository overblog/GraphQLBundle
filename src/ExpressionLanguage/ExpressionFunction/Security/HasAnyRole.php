<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyRole',
            static function ($roles): string {
                return \sprintf('$globalVariable->get(\'security\')->hasAnyRole(%s)', $roles);
            },
            static function ($arguments, $roles): bool {
                return $arguments['globalVariable']->get('security')->hasAnyRole($roles);
            }
        );
    }
}
