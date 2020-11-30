<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Mutation extends ExpressionFunction
{
    public const NAME = 'mutation';

    public function __construct($name = self::NAME)
    {
        parent::__construct(
            $name,
            function (string $alias, ...$args) {
                $args = count($args) > 0 ? ', '.join(', ', $args) : '';

                return "$this->gqlServices->mutation({$alias}{$args})";
            }
        );
    }
}
