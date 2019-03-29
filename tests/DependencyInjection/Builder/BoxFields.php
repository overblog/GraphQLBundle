<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class BoxFields implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $mapping = [];

        foreach ($config as $boxField => $itemType) {
            $boxType = $itemType.'Box';

            $mapping['fields'][$boxField] = ['type' => $boxType.'!'];
            $mapping['types'][$boxType] = [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'isEmpty' => ['type' => 'Boolean!'],
                        'item' => ['type' => $itemType],
                    ],
                ],
            ];
        }

        return $mapping;
    }
}
