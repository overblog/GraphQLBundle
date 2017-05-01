<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Mutation extends ExpressionFunction
{
    public function __construct($name = 'mutation')
    {
        parent::__construct(
            $name,
            function ($alias, $args = '[]') {
                return sprintf('$container->get(\'overblog_graphql.mutation_resolver\')->resolve([%s, %s])', $alias, $args);
            }
        );
    }
}
