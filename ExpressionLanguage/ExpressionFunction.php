<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction as BaseExpressionFunction;

class ExpressionFunction extends BaseExpressionFunction
{
    public function __construct($name, callable $compiler)
    {
        parent::__construct($name, $compiler, function () {
            throw new \RuntimeException('No need evaluator.');
        });
    }
}
