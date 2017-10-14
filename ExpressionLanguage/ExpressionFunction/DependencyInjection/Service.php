<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Service extends ExpressionFunction
{
    public function __construct($name = 'service')
    {
        parent::__construct(
            $name,
            function ($value) {
                return sprintf('$container->get(%s)', $value);
            }
        );
    }
}
