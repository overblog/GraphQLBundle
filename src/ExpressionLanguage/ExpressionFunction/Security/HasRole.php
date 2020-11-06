<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class HasRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasRole',
            fn ($role) => "$this->gqlServices->get('security')->hasRole($role)",
            static fn (array $arguments, $role) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get('security')->hasRole($role)
        );
    }
}
