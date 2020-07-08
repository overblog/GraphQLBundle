<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isFullyAuthenticated',
            static function (): string {
                return '$globalVariable->get(\'security\')->isFullyAuthenticated()';
            },
            static function ($arguments): bool {
                return $arguments['globalVariable']->get('security')->isFullyAuthenticated();
            }
        );
    }
}
