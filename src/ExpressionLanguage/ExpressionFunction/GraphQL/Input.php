<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Input extends ExpressionFunction
{
    public function __construct($name = 'input')
    {
        parent::__construct(
            $name,
            function ($type, $data) {
                return \sprintf('$globalVariable->get(\'container\')->get(\'overblog_graphql.input_builder\')->getInstanceAndValidate(%s, %s, $info)', $type, $data);
            }
        );
    }
}
