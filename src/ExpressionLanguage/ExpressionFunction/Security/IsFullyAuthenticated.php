<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class IsFullyAuthenticated extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isFullyAuthenticated',
            fn () => "$this->gqlServices->get('security')->isFullyAuthenticated()",
            fn (array $arguments) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get('security')->isFullyAuthenticated()
        );
    }
}
