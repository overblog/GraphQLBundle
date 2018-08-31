<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Parameter extends ExpressionFunction
{
    public function __construct($name = 'parameter')
    {
        parent::__construct(
            $name,
            function ($value) {
                return \sprintf('$globalVariable->get(\'container\')->getParameter(%s)', $value);
            }
        );
    }
}
