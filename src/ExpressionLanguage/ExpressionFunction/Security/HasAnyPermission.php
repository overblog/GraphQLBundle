<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'hasAnyPermission',
            static function ($object, $permissions): string {
                return \sprintf('$globalVariables->get(\'security\')->hasAnyPermission(%s, %s)', $object, $permissions);
            },
            function ($_, $object, $permissions) use ($security): bool {
                return $security->hasAnyPermission($object, $permissions);
            }
        );
    }
}
