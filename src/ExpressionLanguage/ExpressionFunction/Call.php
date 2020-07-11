<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Call extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'call',
            fn (string $target, string $args = '[]') => "$target(...$args)",
            fn ($_, callable $target, array $args) => $target(...$args)
        );
    }
}
