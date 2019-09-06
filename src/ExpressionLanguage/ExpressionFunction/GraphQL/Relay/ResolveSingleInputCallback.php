<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\Exception\EvaluatorIsNotAllowedException;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class ResolveSingleInputCallback extends ExpressionFunction
{
    public function __construct($name = 'resolveSingleInputCallback')
    {
        parent::__construct(
            $name,
            function ($resolveSingleInput) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$resolveSingleInput.'; }';

                return $code;
            },
            // This expression function is not designed to be used by it's evaluator
            function () {
                throw new EvaluatorIsNotAllowedException('resolveSingleInputCallback');
            }
        );
    }
}
