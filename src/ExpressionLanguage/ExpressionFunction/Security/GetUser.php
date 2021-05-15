<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;

final class GetUser extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'getUser',
            fn () => "$this->gqlServices->get('".Security::class.'\')->getUser()',
            static fn (array $arguments) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get(Security::class)->getUser()
        );
    }
}
