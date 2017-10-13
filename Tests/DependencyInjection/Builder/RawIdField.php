<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class RawIdField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        return [
            'description' => 'The raw ID of an object',
            'type' => 'Int!',
            'resolve' => '@=value.id',
        ];
    }
}
