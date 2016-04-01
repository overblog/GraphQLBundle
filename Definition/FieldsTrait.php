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
use GraphQL\Utils;

trait FieldsTrait
{
    /**
     * @var FieldDefinition[]
     */
    private $_fields;

    /**
     * @return FieldDefinition[]
     */
    public function getFields()
    {
        if (null === $this->_fields) {
            $fields = isset($this->config['fields']) ? $this->config['fields'] : [];
            $fields = is_callable($fields) ? call_user_func($fields) : $fields;
            $this->_fields = FieldDefinition::createMap($fields);
        }

        return $this->_fields;
    }

    /**
     * @param string $name
     *
     * @return FieldDefinition
     *
     * @throws \Exception
     */
    public function getField($name)
    {
        if (null === $this->_fields) {
            $this->getFields();
        }
        Utils::invariant(isset($this->_fields[$name]), "Field '%s' is not defined for type '%s'", $name, $this->name);

        return $this->_fields[$name];
    }
}
