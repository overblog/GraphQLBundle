<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class NodeDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
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
