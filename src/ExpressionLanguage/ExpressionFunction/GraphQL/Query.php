<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Query extends ExpressionFunction
{
    public const NAME = 'query';

    public function __construct($name = self::NAME)
    {
        parent::__construct(
            $name,
            function (string $alias, ...$args) {
                $args = (count($args) > 0) ? (', '.join(', ', $args)) : '';

                return "$this->gqlServices->query({$alias}{$args})";
            }
        );
    }
}
