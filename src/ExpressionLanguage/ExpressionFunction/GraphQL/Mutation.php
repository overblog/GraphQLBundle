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
                $count = count($args);

                // TODO: remove the following if-else-block in 1.0
                if (1 === $count && '$' !== $args[0][0]) {
                    @trigger_error(
                        "The signature of the 'mutation' expression function has been changed. Use a variable-length argument list, instead of a signle array argument. For more info visit: https://github.com/overblog/GraphQLBundle/issues/775",
                        E_USER_DEPRECATED
                    );

                    $args = ', '.$args[0];
                } else {
                    $args = $count > 0 ? ', '.join(', ', $args) : '';
                }

                // TODO: uncomment the following line in 1.0
                // $args = $count > 0 ? ', '.join(', ', $args) : '';

                return "$this->gqlServices->mutation({$alias}{$args})";
            }
        );
    }
}
