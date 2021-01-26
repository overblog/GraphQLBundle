<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IsGranted extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isGranted',
            fn ($attributes, $object = 'null') => "$this->gqlServices->get('security')->isGranted($attributes, $object)",
            static fn (array $arguments, $attributes, $object = null) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get('security')->isGranted($attributes, $object)
        );
    }
}
