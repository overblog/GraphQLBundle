<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isAuthenticated',
            static function (): string {
                return '$globalVariable->get(\'security\')->isAuthenticated()';
            },
            static function ($arguments): bool {
                return $arguments['globalVariable']->get('security')->isAuthenticated();
            }
        );
    }
}
