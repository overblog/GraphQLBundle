<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Resolver extends ExpressionFunction
{
    public function __construct($name = 'resolver')
    {
        parent::__construct(
            $name,
            function ($alias, $args = '[]') {
                return sprintf('$container->get(\'overblog_graphql.resolver_resolver\')->resolve([%s, %s])', $alias, $args);
            }
        );
    }
}
