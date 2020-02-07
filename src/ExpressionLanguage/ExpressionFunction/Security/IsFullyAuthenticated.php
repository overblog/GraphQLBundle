<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'isFullyAuthenticated',
            static function (): string {
                return '$globalVariable->get(\'security\')->isFullyAuthenticated()';
            },
            static function () use ($security): bool {
                return $security->isFullyAuthenticated();
            }
        );
    }
}
