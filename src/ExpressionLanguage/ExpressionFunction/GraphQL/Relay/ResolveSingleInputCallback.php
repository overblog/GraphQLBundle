<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class ResolveSingleInputCallback extends ExpressionFunction
{
    public function __construct(GlobalVariables $globalVariables, $name = 'resolveSingleInputCallback')
    {
        parent::__construct(
            $name,
            function ($resolveSingleInput) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$resolveSingleInput.'; }';

                return $code;
            },
            // TODO: finish this callback
            function ($arguments, $mutateAndGetPayload) use ($globalVariables): void {
//                [
//                    'context' => $context,
//                    'args'    => $args,
//                    'info'    => $info
//                ] = $arguments;
//
//                return function($value) use ($mutateAndGetPayload, $globalVariables, $args, $context, $info) {
//                    return $mutateAndGetPayload;
//                };
                throw new \RuntimeException("The expression function 'resolveSingleInputCallback' is not yet finished and therefore is not allowed to be used.");
            }
        );
    }
}
