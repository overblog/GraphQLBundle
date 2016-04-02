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
use GraphQL\Type\Definition\ObjectType as BaseObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils;

class ObjectType extends BaseObjectType
{
    private $isTypeOf;

    /**
     * @var FieldDefinition[]
     */
    private $fields;

    /**
     * @param array $config
     *
     * @todo open PR on lib to ease inheritance
     */
    public function __construct(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'fields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME | Config::MAYBE_THUNK
            ),
            'description' => Config::STRING,
            'interfaces' => Config::arrayOf(
                Config::INTERFACE_TYPE,
                Config::MAYBE_THUNK
            ),
            'isTypeOf' => Config::CALLBACK, // ($value, ResolveInfo $info) => boolean
            'resolveField' => Config::CALLBACK,
        ]);

        $this->name = $config['name'];
        $this->description = isset($config['description']) ? $config['description'] : null;
        $this->resolveFieldFn = isset($config['resolveField']) ? $config['resolveField'] : null;
        $this->isTypeOf = isset($config['isTypeOf']) ? $config['isTypeOf'] : null;
        $this->config = $config;

        if (isset($config['interfaces'])) {
            InterfaceType::addImplementationToInterfaces($this);
        }
    }

    /**
     * @return FieldDefinition[]
     */
    public function getFields()
    {
        if (null === $this->fields) {
            $fields = isset($this->config['fields']) ? $this->config['fields'] : [];
            $fields = is_callable($fields) ? call_user_func($fields) : $fields;
            $this->fields = FieldDefinition::createMap($fields);
        }

        return $this->fields;
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
        if (null === $this->fields) {
            $this->getFields();
        }
        Utils::invariant(isset($this->fields[$name]), "Field '%s' is not defined for type '%s'", $name, $this->name);

        return $this->fields[$name];
    }

    /**
     * @param $value
     * @return bool|null
     */
    public function isTypeOf($value, ResolveInfo $info)
    {
        return isset($this->isTypeOf) ? call_user_func($this->isTypeOf, $value, $info) : null;
    }
}
