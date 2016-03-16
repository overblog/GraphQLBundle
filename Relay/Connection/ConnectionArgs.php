<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class ConnectionArgs implements MappingInterface
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function toMappingDefinition(array $config)
    {
        return [
            'after' => [
                'type' => Type::string(),
            ],
            'first' => [
                'type' => Type::int(),
            ],
            'before' => [
                'type' => Type::string(),
            ],
            'last' => [
                'type' => Type::int(),
            ],
        ];
    }
}
