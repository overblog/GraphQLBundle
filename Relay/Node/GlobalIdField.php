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
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Definition\Type;
use Overblog\GraphQLBundle\Resolver\Resolver;

class GlobalIdField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'typeName' => Config::STRING,
            'idFetcher' => Config::CALLBACK,
        ]);

        $name = $config['name'];
        $typeName = isset($config['typeName']) ? $config['typeName'] : null;
        $idFetcher = isset($config['idFetcher']) ? $config['idFetcher'] : null;

        return [
            'name' => $name,
            'description' => 'The ID of an object',
            'type' => Type::nonNull(Type::id()),
            'resolve' => function ($obj, $args, ResolveInfo $info) use ($idFetcher, $typeName) {
                return GlobalId::toGlobalId(
                    !empty($typeName) ? $typeName : $info->parentType->name,
                    is_callable($idFetcher) ? call_user_func_array($idFetcher, [$obj, $info]) : Resolver::valueFromObjectOrArray($obj, 'id')
                );
            },
        ];
    }
}
