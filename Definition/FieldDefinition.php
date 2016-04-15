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

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\FieldDefinition as BaseFieldDefinition;

class FieldDefinition extends BaseFieldDefinition
{
    public static function createMap(array $fields)
    {
        $map = [];
        foreach ($fields as $name => $field) {
            if (!isset($field['name'])) {
                $field['name'] = $name;
            }
            $map[$name] = static::create($field);
        }

        return $map;
    }

    /**
     * @param array $field
     *
     * @return FieldDefinition
     */
    public static function create($field)
    {
        Config::validate($field, static::getDefinition());

        return new static($field);
    }
}
