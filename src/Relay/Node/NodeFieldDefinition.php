<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Node;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function is_string;
use function strpos;
use function substr;

final class NodeFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        if (!isset($config['idFetcher']) || !is_string($config['idFetcher'])) {
            throw new InvalidArgumentException('Node "idFetcher" config is invalid.');
        }

        $idFetcher = $this->cleanIdFetcher($config['idFetcher']);
        $nodeInterfaceType = isset($config['nodeInterfaceType']) && is_string($config['nodeInterfaceType']) ? $config['nodeInterfaceType'] : null;

        return [
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => [
                'id' => ['type' => 'ID!', 'description' => 'The ID of an object'],
            ],
            'resolve' => "@=resolver('relay_node_field', [args, context, info, idFetcherCallback($idFetcher)])",
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
