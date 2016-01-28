<?php

namespace Overblog\GraphBundle\Relay\Node;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class NodeInterfaceType extends InterfaceType
{
    public function __construct(array $config = [])
    {
        Config::validate($config, [
            'resolveType' => Config::CALLBACK
        ]);

        $resolveType = isset($config['resolveType']) ? $config['resolveType'] : null;

        parent::__construct([
            'name' => 'NodeInterface',
            'description' => 'Fetches an object given its ID',
            'fields' => [
                'id' => [
                    'type' =>  Type::nonNull(Type::id()),
                    'description' => 'The ID of an object'
                ]
            ],
            'resolveType' => $resolveType,
        ]);
    }
}
