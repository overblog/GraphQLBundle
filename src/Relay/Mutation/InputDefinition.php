<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class InputDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $alias = \preg_replace('/(.*)?Type$/', '$1', $config['class_name']);
        $name = $config['name'];
        $name = \preg_replace('/(.*)?Input$/', '$1', $name).'Input';

        $inputFields = empty($config['fields']) || !\is_array($config['fields']) ? [] : $config['fields'];

        return [
            $alias => [
                'type' => 'input-object',
                'config' => [
                    'name' => $name,
                    'fields' => \array_merge(
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
