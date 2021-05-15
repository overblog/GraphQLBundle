<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyRole extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyRole',
            fn ($roles) => "$this->gqlServices->get('".Security::class."')->hasAnyRole($roles)",
            static fn (array $arguments, $roles) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get(Security::class)->hasAnyRole($roles)
        );
    }
}
