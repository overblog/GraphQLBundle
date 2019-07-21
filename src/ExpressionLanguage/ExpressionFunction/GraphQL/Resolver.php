<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;

final class Resolver extends ExpressionFunction
{
    public function __construct(ResolverResolver $resolver, $name = 'resolver')
    {
        parent::__construct(
            $name,
            function (string $alias, string $args = '[]'): string {
                return "\$globalVariable->get('resolverResolver')->resolve([$alias, $args])";
            },
            function ($arguments, $alias, $args) use ($resolver) {
                return $resolver->resolve([$alias, $args]);
            }
        );
    }
}
