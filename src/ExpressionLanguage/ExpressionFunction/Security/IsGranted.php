<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Security\Security;

final class IsGranted extends ExpressionFunction
{
    public function __construct(Security $security)
    {
        parent::__construct(
            'isGranted',
            static function ($attributes, $object = 'null'): string {
                return \sprintf('$globalVariables->get(\'security\')->isGranted(%s, %s)', $attributes, $object);
            },
            static function ($_, $attributes, $object = null) use ($security): bool {
                return $security->isGranted($attributes, $object);
            }
        );
    }
}
