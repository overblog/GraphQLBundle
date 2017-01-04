<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class NodeFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        if (!isset($config['idFetcher']) || !is_string($config['idFetcher'])) {
            throw new \InvalidArgumentException('Node "idFetcher" config is invalid.');
        }

        $idFetcher = $this->cleanIdFetcher($config['idFetcher']);
        $nodeInterfaceType = isset($config['nodeInterfaceType']) && is_string($config['nodeInterfaceType']) ? $config['nodeInterfaceType'] : null;

        return [
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => [
                'id' => ['type' => 'ID!', 'description' => 'The ID of an object'],
            ],
            'resolve' => "@=resolver('relay_node_field', [args, idFetcherCallback($idFetcher)])",
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
