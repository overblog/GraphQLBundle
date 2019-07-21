<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IdFetcherCallback extends ExpressionFunction
{
    public function __construct(GlobalVariables $globalVariables, $name = 'idFetcherCallback')
    {
        parent::__construct(
            $name,
            function ($idFetcher) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$idFetcher.'; }';

                return $code;
            },
            // TODO: finish this callback
            function ($arguments, $idFetcher) use ($globalVariables): callable {
                [
                    'context' => $context,
                    'args'    => $args,
                    'info'    => $info
                ] = $arguments;

                return function ($value) use ($idFetcher, $globalVariables, $args, $context, $info) {
                    return $idFetcher;
                };
            }
        );
    }
}
