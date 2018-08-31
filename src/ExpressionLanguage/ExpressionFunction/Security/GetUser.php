<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class GetUser extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getUser',
            function () {
                return \sprintf('\%s::getUser($globalVariable)', Helper::class);
            }
        );
    }
}
