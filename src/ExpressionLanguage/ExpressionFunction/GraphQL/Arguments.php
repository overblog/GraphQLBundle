<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Arguments extends ExpressionFunction
{
    public function __construct($name = 'arguments')
    {
        parent::__construct(
            $name,
            function ($mapping, $data) {
                return \sprintf('$globalVariable->get(\'container\')->get(\'overblog_graphql.arguments_transformer\')->getArguments(%s, %s, $info)', $mapping, $data);
            }
        );
    }
}
