<?php

namespace Overblog\GraphQLBundle\Relay\Node;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\FieldInterface;

class RawIdField implements FieldInterface
{
    public function toFieldsDefinition(array $config)
    {
        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'idFetcher' => Config::CALLBACK
        ]);

        $name = $config['name'];
        $idFetcher = isset($config['idFetcher']) ? $config['idFetcher'] : null;

        return [
            'name' => $name,
            'description' => 'The raw ID of an object',
            'type' => Type::nonNull(Type::int()),
            'resolve' => function($obj, $args, ResolveInfo $info) use ($idFetcher) {
                return is_callable($idFetcher) ? $idFetcher($obj, $info) : $obj->id;
            }
        ];
    }
}
