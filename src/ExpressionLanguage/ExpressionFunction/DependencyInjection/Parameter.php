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
            function (string $value) {
                return "\$globalVariable->get('container')->getParameter($value)";
            },
            function ($arguments, $paramName) use ($parameterBag) {
                return $parameterBag->get($paramName);
            }
        );
    }
}
