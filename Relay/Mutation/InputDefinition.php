<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class InputDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $name = $config['name'];
        $name = preg_replace('/(.*)?Input$/', '$1', $name).'Input';

        $inputFields = empty($config['fields']) || !is_array($config['fields']) ? [] : $config['fields'];

        return [
            $name => [
                'type' => 'input-object',
                'config' => [
                    'name' => $name,
                    'fields' => array_merge(
                        $inputFields,
                        [
                            'clientMutationId' => ['type' => 'String'],
                        ]
                    ),
                ],
            ],
        ];
    }
}
