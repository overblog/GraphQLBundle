<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

interface MappingInterface
{
    public function toMappingDefinition(array $config): array;
}
