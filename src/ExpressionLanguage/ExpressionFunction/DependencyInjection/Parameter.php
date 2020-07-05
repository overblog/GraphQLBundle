<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Parameter extends ExpressionFunction
{
    public function __construct(ParameterBagInterface $parameterBag, $name = 'parameter')
    {
        parent::__construct(
            $name,
            fn (string $value) => "$this->globalVars->get('container')->getParameter($value)",
            fn ($arguments, $paramName) => $parameterBag->get($paramName)
        );
    }
}
