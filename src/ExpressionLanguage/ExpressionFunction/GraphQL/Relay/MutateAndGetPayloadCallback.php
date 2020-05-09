<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class MutateAndGetPayloadCallback extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'mutateAndGetPayloadCallback',
            function ($mutateAndGetPayload) {
                $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                $code .= 'return '.$mutateAndGetPayload.'; }';

                return $code;
            }
        );
    }
}
