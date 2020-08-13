<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class Parameter extends ExpressionFunction
{
    public function __construct($name = 'parameter')
    {
        parent::__construct(
            $name,
            fn (string $value) => "$this->globalVars->get('container')->getParameter($value)",
            static fn (array $arguments, $paramName) => $arguments[TypeGenerator::GLOBAL_VARS]->get('container')->getParameter($paramName)
        );
    }
}
