<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class GetType extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getType',
            fn (string $alias) => "$this->gqlServices->getType($alias)"
        );
    }
}
