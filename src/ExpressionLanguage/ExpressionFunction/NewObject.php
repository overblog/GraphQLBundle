<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class NewObject extends ExpressionFunction
{
    public function __construct($name = 'newObject')
    {
        parent::__construct(
            $name,
            function ($className, $args = '[]') {
                return sprintf('(new \ReflectionClass(%s))->newInstanceArgs(%s)', $className, $args);
            }
        );
    }
}
