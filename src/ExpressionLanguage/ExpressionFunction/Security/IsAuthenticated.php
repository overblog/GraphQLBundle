<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class IsAuthenticated extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'isAuthenticated',
            static function (): string {
                return '$globalVariables->get(\'security\')->isAuthenticated()';
            },
            static function () use ($security): bool {
                return $security->isAuthenticated();
            }
        );
    }
}
