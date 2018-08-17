<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class FromGlobalID extends ExpressionFunction
{
    public function __construct($name = 'fromGlobalId')
    {
        parent::__construct(
            $name,
            function ($globalId) {
                return sprintf(
                    '\%s::fromGlobalId(%s)',
                    \Overblog\GraphQLBundle\Relay\Node\GlobalId::class,
                    $globalId
                );
            }
        );
    }
}
