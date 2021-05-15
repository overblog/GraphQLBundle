<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;

final class IsAnonymous extends ExpressionFunction
{
    public function __construct()
    {
        parent::__construct(
            'isAnonymous',
            fn () => "$this->gqlServices->get('".Security::class.'\')->isAnonymous()',
            static fn (array $arguments) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get(Security::class)->isAnonymous()
        );
    }
}
