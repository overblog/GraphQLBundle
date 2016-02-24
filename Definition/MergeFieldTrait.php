<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Definition;

trait MergeFieldTrait
{
    protected function getFieldsWithDefaults($fields, array $defaultFields, $forceArray = true)
    {
        $callback = function () use ($fields, $defaultFields) {
            if (empty($fields)) {
                return $defaultFields;
            }

            if (is_callable($fields)) {
                $fields = $fields();
            }

            if (!is_array($fields)) {
                $fields = [$fields];
            }

            return array_merge($fields, $defaultFields);
        };

        if ($forceArray) {
            return $callback();
        } else {
            return $callback;
        }
    }
}
