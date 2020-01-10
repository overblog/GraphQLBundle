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
            static function (): string {
                return '$globalVariable->get(\'security\')->isGranted()';
            },
            static function () use ($security): bool {
                return $security->isGranted();
            }
        );
    }
}
