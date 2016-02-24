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
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\FieldInterface;

class RawIdField implements FieldInterface
{
    public function toFieldDefinition(array $config)
    {
        Config::validate($config, [
            'name'      => Config::STRING | Config::REQUIRED,
            'idFetcher' => Config::CALLBACK,
        ]);

        $name = $config['name'];
        $idFetcher = isset($config['idFetcher']) ? $config['idFetcher'] : null;

        return [
            'name'        => $name,
            'description' => 'The raw ID of an object',
            'type'        => Type::nonNull(Type::int()),
            'resolve'     => function ($obj, $args, ResolveInfo $info) use ($idFetcher) {
                return is_callable($idFetcher) ? $idFetcher($obj, $info) : $obj->id;
            },
        ];
    }
}
