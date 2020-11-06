<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Relay\Node\GlobalId as GlobalIdHelper;
use function sprintf;

final class FromGlobalID extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'fromGlobalId',
            static fn ($globalId) => sprintf('\%s::fromGlobalId(%s)', GlobalIdHelper::class, $globalId),
            static fn ($_, $globalId) => GlobalIdHelper::fromGlobalId($globalId)
        );
    }
}
