<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function is_string;
use function strpos;
use function substr;
use function var_export;

final class GlobalIdFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $typeName = isset($config['typeName']) && is_string($config['typeName']) ? var_export($config['typeName'], true) : 'null';
        $idFetcher = isset($config['idFetcher']) && is_string($config['idFetcher']) ? $this->cleanIdFetcher($config['idFetcher']) : 'null';

        return [
            'description' => 'The ID of an object',
            'type' => 'ID!',
            'resolve' => "@=resolver('relay_globalid_field', [value, info, $idFetcher, $typeName])",
        ];
    }

    private function cleanIdFetcher(string $idFetcher): string
    {
        $cleanIdFetcher = $idFetcher;

        if (0 === strpos($idFetcher, '@=')) {
            $cleanIdFetcher = substr($idFetcher, 2);
        }

        return $cleanIdFetcher;
    }
}
