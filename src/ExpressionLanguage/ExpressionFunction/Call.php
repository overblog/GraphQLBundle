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
            function ($target, $args = '[]', $static = false) {
                if ($static) {
                    return \sprintf('\call_user_func_array(%s, %s)', $target, $args);
                } else {
                    return \sprintf('%s(...%s)', $target, $args);
                }
            }
        );
    }
}
