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
            static function (string $value) {
                return "\$globalVariable->get('container')->getParameter($value)";
            },
            static function ($arguments, $paramName) {
                return $arguments['globalVariable']->get('container')->getParameter($paramName);
            }
        );
    }
}
