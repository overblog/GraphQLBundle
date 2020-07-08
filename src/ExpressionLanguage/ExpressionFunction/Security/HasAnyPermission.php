<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyPermission',
            static function ($object, $permissions): string {
                return \sprintf('$globalVariable->get(\'security\')->hasAnyPermission(%s, %s)', $object, $permissions);
            },
            static function ($arguments, $object, $permissions): bool {
                return $arguments['globalVariable']->get('security')->hasAnyPermission($object, $permissions);
            }
        );
    }
}
