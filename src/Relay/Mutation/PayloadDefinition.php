<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function array_merge;
use function is_array;
use function preg_replace;

final class PayloadDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $alias = preg_replace('/(.*)?Type$/', '$1', $config['class_name']);
        $name = $config['name'];
        $name = preg_replace('/(.*)?Payload$/', '$1', $name).'Payload';
        $outputFields = empty($config['fields']) || !is_array($config['fields']) ? [] : $config['fields'];

        return [
            $alias => [
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
