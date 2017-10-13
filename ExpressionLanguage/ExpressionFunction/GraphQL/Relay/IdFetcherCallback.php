<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IdFetcherCallback extends ExpressionFunction
{
    public function __construct($name = 'idFetcherCallback')
    {
        parent::__construct(
            $name,
            function ($idFetcher) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$idFetcher.'; }';

                return $code;
            }
        );
    }
}
