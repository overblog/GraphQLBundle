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
            function (string $target, string $args = '[]') {
                return "$target(...$args)";
            },
            function ($_, callable $target, array $args) {
                return $target(...$args);
            }
        );
    }
}
