<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class PayloadDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $name = $config['name'];
        $name = preg_replace('/(.*)?Payload$/', '$1', $name).'Payload';
        $outputFields = empty($config['fields']) || !is_array($config['fields']) ? [] : $config['fields'];

        return [
            $name => [
                'type' => 'object',
                'config' => [
                    'name' => $name,
                    'fields' => array_merge(
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
