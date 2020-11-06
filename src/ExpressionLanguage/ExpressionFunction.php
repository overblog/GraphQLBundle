<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\ExpressionLanguage\ExpressionFunction as BaseExpressionFunction;

class ExpressionFunction extends BaseExpressionFunction
{
    protected string $globalVars = '$'.TypeGenerator::GLOBAL_VARS;

    public function __construct(string $name, callable $compiler, ?callable $evaluator = null)
    {
        if (null === $evaluator) {
            $evaluator = new EvaluatorIsNotAllowedException($name);
        }

        parent::__construct($name, $compiler, $evaluator);
    }
}
