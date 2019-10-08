<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IdFetcherCallback extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'idFetcherCallback',
            function ($idFetcher) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$idFetcher.'; }';

                return $code;
            }
        );
    }
}
