<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Murtukov\PHPCodeGenerator\Closure;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class MutateAndGetPayloadCallback extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'mutateAndGetPayloadCallback',
            static fn ($mutateAndGetPayload) => (
                Closure::new()
                    ->addArgument('value')
                    ->bindVars(TypeGenerator::GLOBAL_VARS, 'args', 'context', 'info')
                    ->append("return $mutateAndGetPayload")
                    ->generate()
            )
        );
    }
}
