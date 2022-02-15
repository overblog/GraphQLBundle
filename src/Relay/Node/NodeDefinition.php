<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class NodeDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $name = $config['name'];

        return [
            $name => [
                'type' => 'interface',
                'config' => [
                    'name' => $name,
                    'description' => 'Fetches an object given its ID',
                    'fields' => [
                        'id' => [
                            'type' => 'ID!',
                            'description' => 'The ID of an object',
                        ],
                    ],
                    'typeResolver' => $config['typeResolver'] ?? $config['resolveType'] ?? null,
                ],
            ],
        ];
    }
}
