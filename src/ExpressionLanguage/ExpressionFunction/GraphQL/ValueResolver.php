<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class ValueResolver extends ExpressionFunction
{
    public function __construct($name = 'value_resolver')
    {
        parent::__construct(
            $name,
            function ($args = '[]', $method = null) {
                return \sprintf('call_user_func_array([$value, %s], array_values(%s));', $method ?: '$info->fieldName', $args);
            }
        );
    }
}
