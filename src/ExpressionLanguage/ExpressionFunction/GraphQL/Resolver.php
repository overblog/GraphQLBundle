<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

/**
 * @deprecated This class has been deprecated since 0.14 and will be removed in 1.0. Use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Query instead.
 * @codeCoverageIgnore
 */
final class Resolver extends ExpressionFunction
{
    public function __construct($name = 'resolver')
    {
        parent::__construct(
            $name,
            function (string $alias, string $args = '[]') {
                @trigger_error(
                    "The expression function 'resolver' has been deprecated since 0.14 and will be removed in 1.0. Use 'query' instead. For more info visit: https://github.com/overblog/GraphQLBundle/issues/775",
                    E_USER_DEPRECATED
                );

                return "$this->gqlServices->query($alias, ...$args)";
            }
        );
    }
}
