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
use GraphQL\Utils;

class ObjectType extends BaseObjectType
{
    use FieldsTrait;

    private $_isTypeOf;

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
        $this->_isTypeOf = isset($config['isTypeOf']) ? $config['isTypeOf'] : null;
        $this->config = $config;

        if (isset($config['interfaces'])) {
            InterfaceType::addImplementationToInterfaces($this);
        }
    }
}
