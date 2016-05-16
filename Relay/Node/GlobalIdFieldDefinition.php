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
use Overblog\GraphQLBundle\Definition\Type;

class GlobalIdFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $typeName = isset($config['typeName']) && is_string($config['typeName']) ? var_export($config['typeName'], true) : 'null';
        $idFetcher = isset($config['idFetcher']) && is_string($config['idFetcher']) ? $this->cleanIdFetcher($config['idFetcher']) : 'null';

        return [
            'description' => 'The ID of an object',
            'type' => 'ID!',
            'resolve' => "@=resolver('relay_globalid_field', [value, info, $idFetcher, $typeName])",
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
