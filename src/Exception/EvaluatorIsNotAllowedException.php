<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Exception;

use Throwable;

class EvaluatorIsNotAllowedException extends \Exception
{
    public function __construct($expressionFunctionName, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            "The expression function '$expressionFunctionName' cannot be used by it's evaluator.",
            $code,
            $previous
        );
    }
}
