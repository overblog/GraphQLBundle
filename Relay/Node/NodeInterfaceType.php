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
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class NodeInterfaceType extends InterfaceType
{
    public function __construct(array $config = [])
    {
        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'resolveType' => Config::CALLBACK,
        ]);

        $resolveType = isset($config['resolveType']) ? $config['resolveType'] : null;

        parent::__construct([
            'name' => $config['name'],
            'description' => 'Fetches an object given its ID',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::id()),
                    'description' => 'The ID of an object',
                ],
            ],
            'resolveType' => $resolveType,
        ]);
    }
}
