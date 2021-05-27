<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;

final class HasPermission extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'hasPermission',
            fn ($object, $permission) => "$this->gqlServices->get('".Security::class."')->hasPermission($object, $permission)",
            static fn (array $arguments, $object, $permission) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get(Security::class)->hasPermission($object, $permission)
        );
    }
}
