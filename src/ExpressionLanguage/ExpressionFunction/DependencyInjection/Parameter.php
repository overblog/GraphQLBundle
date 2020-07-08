<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Parameter extends ExpressionFunction
{
    public function __construct($name = 'parameter')
    {
        parent::__construct(
            $name,
            fn (string $value) => "$this->globalVars->get('container')->getParameter($value)",
            static fn (array $arguments, $paramName) => $arguments['globalVariables']->get('container')->getParameter($paramName)
        );
    }
}
