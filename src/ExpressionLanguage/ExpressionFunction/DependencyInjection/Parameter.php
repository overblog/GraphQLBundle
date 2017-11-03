<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\DependencyInjection;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class Parameter extends ExpressionFunction
{
    public function __construct($name = 'parameter')
    {
        parent::__construct(
            $name,
            function ($value) {
                return sprintf('$container->getParameter(%s)', $value);
            }
        );
    }
}
