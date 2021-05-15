<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Generator\TypeGenerator;

final class Parameter extends ExpressionFunction
{
    public function __construct($name = 'parameter')
    {
        parent::__construct(
            $name,
            fn (string $value) => "$this->gqlServices->get('service_container')->getParameter($value)",
            static fn (array $arguments, $paramName) => $arguments[TypeGenerator::GRAPHQL_SERVICES]->get('service_container')->getParameter($paramName)
        );
    }
}
