<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Relay\Node\GlobalId;

final class FromGlobalID extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'fromGlobalId',
            function (string $globalId): string {
                return \sprintf('\%s::fromGlobalId(%s)', GlobalId::class, $globalId);
            },
            function ($arguments, $globalId): array {
                return GlobalId::fromGlobalId($globalId);
            }
        );
    }
}
