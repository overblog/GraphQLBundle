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
            static function ($attributes, $subject): string {
                return \sprintf('$globalVariable->get(\'security\')->isGranted(%s, %s)', $attributes, $subject);
            },
            static function ($_, $attributes, $subject) use ($security): bool {
                return $security->isGranted($attributes, $subject);
            }
        );
    }
}
