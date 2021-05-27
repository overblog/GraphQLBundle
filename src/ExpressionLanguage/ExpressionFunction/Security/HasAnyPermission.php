<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;

final class HasAnyPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasAnyPermission',
            fn ($object, $permissions) => "$this->gqlServices->get('".Security::class."')->hasAnyPermission($object, $permissions)",
            static fn (array $arguments, $object, $permissions) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get(Security::class)->hasAnyPermission($object, $permissions)
        );
    }
}
