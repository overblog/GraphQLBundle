<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Builder;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function is_string;

class RelayEdgeFieldsBuilder implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        if (!isset($config['nodeType']) || !is_string($config['nodeType'])) {
            throw new InvalidArgumentException('Using the Relay Edge fields builder, the key "nodeType" defining the GraphQL type of the node is required and must be a string.');
        }

        $nodeType = $config['nodeType'];
        $nodeDescription = $config['nodeDescription'] ?? 'Node of the Edge';

        return [
            'node' => [
                'description' => $nodeDescription,
                'type' => $nodeType,
            ],
            'cursor' => [
                'description' => 'The edge cursor',
                'type' => 'String!',
            ],
        ];
    }
}
