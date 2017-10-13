<?php

namespace Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\GraphQL\Relay\Node\GlobalIdFieldResolver;

final class GlobalIdFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $typeName = isset($config['typeName']) && is_string($config['typeName']) ? var_export($config['typeName'], true) : 'null';
        $idFetcher = isset($config['idFetcher']) && is_string($config['idFetcher']) ? $this->cleanIdFetcher($config['idFetcher']) : 'null';
        $resolver = addslashes(GlobalIdFieldResolver::class);

        return [
            'description' => 'The ID of an object',
            'type' => 'ID!',
            'resolve' => "@=resolver('$resolver', [value, info, $idFetcher, $typeName])",
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
