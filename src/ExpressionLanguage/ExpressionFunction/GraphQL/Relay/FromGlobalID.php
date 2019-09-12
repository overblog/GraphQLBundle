<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Relay\Node\GlobalId as GlobalIdHelper;

final class FromGlobalID extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'fromGlobalId',
            function (string $globalId): string {
                return \sprintf('\%s::fromGlobalId(%s)', GlobalIdHelper::class, $globalId);
            },
            function ($_, $globalId): array {
                return GlobalIdHelper::fromGlobalId($globalId);
            }
        );
    }
}
