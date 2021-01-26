<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class GetUser extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getUser',
            fn () => "$this->gqlServices->get('security')->getUser()",
            static fn (array $arguments) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get('security')->getUser()
        );
    }
}
