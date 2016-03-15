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

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class NodeField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'idFetcher' => Config::CALLBACK | Config::REQUIRED,
            'nodeInterfaceType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
        ]);

        $name = $config['name'];
        $idFetcher = $config['idFetcher'];
        $nodeInterfaceType = $config['nodeInterfaceType'];

        return [
            'name' => $name,
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => [
                'id' => ['type' => Type::nonNull(Type::id()), 'description' => 'The ID of an object'],
            ],
            'resolve' => function ($obj, $args, $info) use ($idFetcher) {
                return call_user_func_array($idFetcher, [$args['id'], $info]);
            },
        ];
    }
}
