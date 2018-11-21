<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class PayloadDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $alias = \preg_replace('/(.*)?Type$/', '$1', $config['class_name']);
        $name = $config['name'];
        $name = \preg_replace('/(.*)?Payload$/', '$1', $name).'Payload';
        $outputFields = empty($config['fields']) || !\is_array($config['fields']) ? [] : $config['fields'];

        return [
            $alias => [
                'type' => 'object',
                'config' => [
                    'name' => $name,
                    'fields' => \array_merge(
                        $outputFields,
                        [
                            'clientMutationId' => ['type' => 'String'],
                        ]
                    ),
                ],
            ],
        ];
    }
}
