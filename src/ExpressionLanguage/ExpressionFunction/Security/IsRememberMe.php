<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class IsRememberMe extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'isRememberMe',
            static function (): string {
                return '$globalVariables->get(\'security\')->isRememberMe()';
            },
            static function () use ($security): bool {
                return $security->isRememberMe();
            }
        );
    }
}
