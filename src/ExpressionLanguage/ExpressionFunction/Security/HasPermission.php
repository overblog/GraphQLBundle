<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasPermission extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasPermission',
            static function ($object, $permission): string {
                return \sprintf('$globalVariables->get(\'security\')->hasPermission(%s, %s)', $object, $permission);
            },
            static function ($_, $object, $permission) use ($security): bool {
                return $security->hasPermission($object, $permission);
            }
        );
    }
}
