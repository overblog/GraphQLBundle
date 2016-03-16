<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Config;

class ResolveCallbackConfigSolution extends AbstractConfigSolution implements UniqueConfigSolutionInterface
{
    public function solve($value, $config = null)
    {
        if (is_callable($value)) {
            return $value;
        }

        return function () use ($value) {
            $args = func_get_args();
            $result = $this->solveUsingExpressionLanguageIfNeeded(
                $value,
                call_user_func_array([$this, 'solveResolveCallbackArgs'], $args)
            );

            return $result;
        };
    }
}
