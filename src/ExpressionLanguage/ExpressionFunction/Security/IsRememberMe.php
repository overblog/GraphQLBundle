<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsRememberMe extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isRememberMe',
            static function (): string {
                return '$globalVariable->get(\'security\')->isRememberMe()';
            },
            static function ($arguments): bool {
                return $arguments['globalVariable']->get('security')->isRememberMe();
            }
        );
    }
}
