<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Call extends ExpressionFunction
{
    public function __construct($name = 'call')
    {
        parent::__construct(
            $name,
            function ($target, $args = '[]') {
                return \sprintf('%s(...%s)', $target, $args);
            }
        );
    }
}
