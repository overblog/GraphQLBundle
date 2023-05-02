<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\Exception\EvaluatorIsNotAllowedException;
use Symfony\Component\ExpressionLanguage\ExpressionFunction as BaseExpressionFunction;

class ExpressionFunction extends BaseExpressionFunction
{
    public function __construct(string $name, callable $compiler, ?callable $evaluator = null)
    {
        if (null === $evaluator) {
            $evaluator = static function (string $name) {
                throw new EvaluatorIsNotAllowedException($name);
            };
        }

        parent::__construct($name, $compiler, $evaluator);
    }
}
