<?php

namespace Overblog\GraphQLBundle\Relay\Connection;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class ConnectionDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $name = $config['name'];
        $namePrefix = preg_replace('/(.*)?Connection$/', '$1', $name);

        //Edge
        $edgeName = $namePrefix.'Edge';
        $edgeFields = empty($config['edgeFields']) || !is_array($config['edgeFields']) ? [] : $config['edgeFields'];
        $nodeType = empty($config['nodeType']) || !is_string($config['nodeType']) ? null : $config['nodeType'];
        $resolveNode = empty($config['resolveNode']) ? null : $config['resolveNode'];
        $resolveCursor = empty($config['resolveCursor']) ? null : $config['resolveNode'];

        //connection
        $connectionName = $namePrefix.'Connection';
        $connectionFields = empty($config['connectionFields']) || !is_array($config['connectionFields']) ? [] : $config['connectionFields'];

        return [
            $edgeName => [
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
            $connectionName => [
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
                                'type' => "[$edgeName]",
                                'description' => 'Information to aid in pagination.',
                            ],
                        ]
                    ),
                ],
            ],
        ];
    }
}
