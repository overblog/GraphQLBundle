<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Resolver\MutationResolver;

final class Mutation extends ExpressionFunction
{
    public function __construct(MutationResolver $resolver, $name = 'mutation')
    {
        parent::__construct(
            $name,
            function ($alias, $args = '[]') {
                return "\$globalVariable->get('mutationResolver')->resolve([$alias, $args])";
            },
            function ($arguments, $alias, $args) use ($resolver) {
                return $resolver->resolve([$alias, $args]);
            }
        );
    }
}
