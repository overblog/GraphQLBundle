<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class GetUser extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getUser',
            static function (): string {
                return '$globalVariable->get(\'security\')->getUser()';
            },
            static function ($arguments) {
                return $arguments['globalVariable']->get('security')->getUser();
            }
        );
    }
}
