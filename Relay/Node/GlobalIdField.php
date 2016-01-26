<?php

namespace Overblog\GraphBundle\Relay\Node;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphBundle\Definition\FieldInterface;

class GlobalIdField implements FieldInterface
{
    public function toFieldsDefinition(array $config)
    {
        Config::validate($config, [
            'typeName' => Config::STRING,
            'idFetcher' => Config::CALLBACK
        ]);

        $typeName = isset($config['typeName'])?$config['typeName']:null;
        $idFetcher = isset($config['idFetcher'])?$config['idFetcher']:null;

        return [
            'name' => 'id',
            'description' => 'The ID of an object',
            'type' => Type::nonNull(Type::id()),
            'resolve' => function($obj, $args, ResolveInfo $info) use($idFetcher, $typeName) {
                return GlobalId::toGlobalId(
                    !empty($typeName) ? $typeName : $info->parentType->name,
                    is_callable($idFetcher) ? $idFetcher($obj, $info) : $obj->id
                );
            }
        ];
    }
}
