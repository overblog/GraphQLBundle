<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Builder;

interface MappingInterface
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function toMappingDefinition(array $config): array;
}
