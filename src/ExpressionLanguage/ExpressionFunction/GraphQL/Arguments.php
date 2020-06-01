<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Transformer\ArgumentsTransformer;

final class Arguments extends ExpressionFunction
{
    public function __construct(ArgumentsTransformer $transformer)
    {
        parent::__construct(
            'arguments',
            function ($mapping, $data) {
                return "\$globalVariables->get('container')->get('overblog_graphql.arguments_transformer')->getArguments($mapping, $data, \$info)";
            },
            function ($arguments, $mapping, $data) use ($transformer) {
                return $transformer->getArguments($mapping, $data, $arguments['info']);
            }
        );
    }
}
