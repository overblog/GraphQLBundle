<?php

namespace Overblog\GraphQLBundle\Relay\Node;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\FieldInterface;

class NodeField implements FieldInterface
{
    public function toFieldsDefinition(array $config)
    {
        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'idFetcher' => Config::CALLBACK | Config::REQUIRED,
            'nodeInterfaceType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED
        ]);

        $name = $config['name'];
        $idFetcher = $config['idFetcher'];
        $nodeInterfaceType = $config['nodeInterfaceType'];

        return [
            'name' => $name,
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => [
                'id' => ['type' => Type::nonNull(Type::id()), 'description' => 'The ID of an object']
            ],
            'resolve' => function($obj, $args, $info) use($idFetcher) {
                if (empty($args['id'])) {
                    throw new \InvalidArgumentException(
                        "Argument \"id\" is required but not provided."
                    );
                }
                return call_user_func_array($idFetcher, [$args['id'], $info]);
            }
        ];
    }
}
