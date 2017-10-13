<?php

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\GraphQL\Relay\Node\NodeFieldResolver;

final class NodeFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        if (!isset($config['idFetcher']) || !is_string($config['idFetcher'])) {
            throw new \InvalidArgumentException('Node "idFetcher" config is invalid.');
        }

        $idFetcher = $this->cleanIdFetcher($config['idFetcher']);
        $nodeInterfaceType = isset($config['nodeInterfaceType']) && is_string($config['nodeInterfaceType']) ? $config['nodeInterfaceType'] : null;
        $resolver = addslashes(NodeFieldResolver::class);

        return [
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => [
                'id' => ['type' => 'ID!', 'description' => 'The ID of an object'],
            ],
            'resolve' => "@=resolver('$resolver', [args, context, info, idFetcherCallback($idFetcher)])",
        ];
    }

    private function cleanIdFetcher($idFetcher)
    {
        $cleanIdFetcher = $idFetcher;

        if (0 === strpos($idFetcher, '@=')) {
            $cleanIdFetcher = substr($idFetcher, 2);
        }

        return $cleanIdFetcher;
    }
}
