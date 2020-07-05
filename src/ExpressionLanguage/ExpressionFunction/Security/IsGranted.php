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
            fn ($attributes, $object = 'null') => "$this->globalVars->get('security')->isGranted($attributes, $object)",
            fn ($_, $attributes, $object = null) => $security->isGranted($attributes, $object)
        );
    }
}
