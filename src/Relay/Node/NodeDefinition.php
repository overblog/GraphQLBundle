<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class NodeDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $name = $config['name'];
        $resolveType = empty($config['resolveType']) ? null : $config['resolveType'];

        return [
            $name => [
                'type' => 'interface',
                'config' => [
                    'name' => $config['name'],
                    'description' => 'Fetches an object given its ID',
                    'fields' => [
                        'id' => [
                            'type' => 'ID!',
                            'description' => 'The ID of an object',
                        ],
                    ],
                    'resolveType' => $resolveType,
                ],
            ],
        ];
    }
}
