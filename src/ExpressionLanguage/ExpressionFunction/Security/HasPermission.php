<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasPermission',
            static function ($object, $permission): string {
                return \sprintf('$globalVariable->get(\'security\')->hasPermission(%s, %s)', $object, $permission);
            },
            static function ($arguments, $object, $permission): bool {
                return $arguments['globalVariable']->get('security')->hasPermission($object, $permission);
            }
        );
    }
}
