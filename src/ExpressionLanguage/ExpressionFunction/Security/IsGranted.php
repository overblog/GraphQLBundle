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
            fn ($attributes, $object = 'null') => "$this->globalVars->get('security')->isGranted($attributes, $object)",
            static fn (array $arguments, $attributes, $object = null) => $arguments['globalVariables']->get('security')->isGranted($attributes, $object)
        );
    }
}
