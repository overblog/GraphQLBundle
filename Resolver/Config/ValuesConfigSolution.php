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

class ValuesConfigSolution extends AbstractConfigSolution implements UniqueConfigSolutionInterface
{
    public function solve($values, $config = null)
    {
        if (!empty($values['values'])) {
            foreach ($values['values'] as $name => &$options) {
                if (isset($options['value'])) {
                    $options['value'] = $this->solveUsingExpressionLanguageIfNeeded($options['value']);
                }
            }
        }

        return $values;
    }
}
