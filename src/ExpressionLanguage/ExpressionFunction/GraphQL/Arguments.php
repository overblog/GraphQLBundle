<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Arguments extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'arguments',
            static function ($mapping, $data) {
                return "\$globalVariable->get('container')->get('overblog_graphql.arguments_transformer')->getArguments($mapping, $data, \$info)";
            },
            static function ($arguments, $mapping, $data) {
                return $arguments['globalVariable']->get('container')->get('overblog_graphql.arguments_transformer')->getArguments($mapping, $data, $arguments['info']);
            }
        );
    }
}
