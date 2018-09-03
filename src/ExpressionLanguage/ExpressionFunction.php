<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction as BaseExpressionFunction;

class ExpressionFunction extends BaseExpressionFunction
{
    public function __construct($name, callable $compiler)
    {
        parent::__construct($name, $compiler, function (): void {
            throw new \RuntimeException('Evaluator is not needed');
        });
    }
}
