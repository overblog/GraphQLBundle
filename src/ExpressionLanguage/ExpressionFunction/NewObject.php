<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class NewObject extends ExpressionFunction
{
    public function __construct($name = 'newObject')
    {
        parent::__construct(
            $name,
            function ($className, $args = '[]') {
                return sprintf('(new \ReflectionClass(%s))->newInstanceArgs(%s)', $className, $args);
            }
        );
    }
}
