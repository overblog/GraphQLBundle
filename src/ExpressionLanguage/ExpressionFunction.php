<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction as BaseExpressionFunction;

class ExpressionFunction extends BaseExpressionFunction
{
    public function __construct($name, callable $compiler)
    {
        parent::__construct($name, $compiler, function () {
            throw new \RuntimeException('No need evaluator.');
        });
    }
}
