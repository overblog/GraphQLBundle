<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class MutateAndGetPayloadCallback extends ExpressionFunction
{
    public function __construct(GlobalVariables $globalVariables, $name = 'mutateAndGetPayloadCallback')
    {
        parent::__construct(
            $name,
            function ($mutateAndGetPayload) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$mutateAndGetPayload.'; }';

                return $code;
            },
            // TODO: finish this callback
            function ($arguments, $mutateAndGetPayload) use ($globalVariables) {
//                [
//                    'context' => $context,
//                    'args'    => $args,
//                    'info'    => $info
//                ] = $arguments;
//
//                return function($value) use ($mutateAndGetPayload, $globalVariables, $args, $context, $info) {
//                    return $mutateAndGetPayload;
//                };
                throw new \RuntimeException("The expression function 'mutateAndGetPayloadCallback' is not yet finished and therefore is not allowed to be used.");
            }
        );
    }
}
