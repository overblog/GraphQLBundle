<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class TimestampFields implements MappingInterface
{
    public function toMappingDefinition(array $config = null): array
    {
        return [
            'createdAt' => [
                'description' => 'The creation date of the object',
                'type' => 'Int!',
                'resolve' => '@=value.createdAt',
            ],
            'updatedAt' => [
                'description' => 'The update date of the object',
                'type' => 'Int!',
                'resolve' => '@=value.updatedAt',
            ],
        ];
    }
}
