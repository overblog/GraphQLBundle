<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsGranted extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isGranted',
            static function ($attributes, $object = 'null'): string {
                return \sprintf('$globalVariable->get(\'security\')->isGranted(%s, %s)', $attributes, $object);
            },
            static function ($arguments, $attributes, $object = null): bool {
                return $arguments['globalVariable']->get('security')->isGranted($attributes, $object);
            }
        );
    }
}
