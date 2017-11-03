<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class IsTypeOf extends ExpressionFunction
{
    public function __construct($name = 'isTypeOf')
    {
        parent::__construct(
            $name,
            function ($className) {
                return sprintf('($className = %s) && $value instanceof $className', $className);
            }
        );
    }
}
