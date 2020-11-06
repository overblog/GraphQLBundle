<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Mutation extends ExpressionFunction
{
    public function __construct($name = 'mutation')
    {
        parent::__construct(
            $name,
            function ($alias, ...$args) {
                $args = (count($args) > 0) ? (', ' . join(', ', $args)) : '';
                return "$this->gqlServices->mutation($alias$args)";
            }
        );
    }
}
