<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Connection;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function array_merge;
use function is_array;
use function is_string;
use function preg_replace;

final class ConnectionDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $name = $config['name'];
        $namePrefix = preg_replace('/(.*)?Connection$/', '$1', $name);
        $aliasPrefix = preg_replace('/(.*)?ConnectionType$/', '$1', $config['class_name']);

        //Edge
        $edgeName = $namePrefix.'Edge';
        $edgeAlias = $aliasPrefix.'Edge';
        $edgeFields = empty($config['edgeFields']) || !is_array($config['edgeFields']) ? [] : $config['edgeFields'];
        $nodeType = empty($config['nodeType']) || !is_string($config['nodeType']) ? null : $config['nodeType'];
        $resolveNode = empty($config['resolveNode']) ? null : $config['resolveNode'];
        $resolveCursor = empty($config['resolveCursor']) ? null : $config['resolveCursor'];

        //connection
        $connectionName = $namePrefix.'Connection';
        $connectionAlias = $aliasPrefix.'Connection';
        $connectionFields = empty($config['connectionFields']) || !is_array($config['connectionFields']) ? [] : $config['connectionFields'];

        return [
            $edgeAlias => [
                'type' => 'object',
                'config' => [
                    'name' => $edgeName,
                    'description' => 'An edge in a connection.',
                    'fields' => array_merge(
                        $edgeFields,
                        [
                            'node' => [
                                'type' => $nodeType,
                                'resolve' => $resolveNode,
                                'description' => 'The item at the end of the edge.',
                            ],
                            'cursor' => [
                                'type' => 'String!',
                                'resolve' => $resolveCursor,
                                'description' => 'A cursor for use in pagination.',
                            ],
                        ]
                    ),
                ],
            ],
            $connectionAlias => [
                'type' => 'object',
                'config' => [
                    'name' => $connectionName,
                    'description' => 'A connection to a list of items.',
                    'fields' => array_merge(
                        $connectionFields,
                        [
                            'pageInfo' => [
                                'type' => 'PageInfo!',
                                'description' => 'Information to aid in pagination.',
                            ],
                            'edges' => [
                                'type' => "[$edgeAlias]",
                                'description' => 'Information to aid in pagination.',
                            ],
                        ]
                    ),
                ],
            ],
        ];
    }
}
