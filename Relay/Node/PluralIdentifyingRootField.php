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
use Overblog\GraphQLBundle\Definition\FieldInterface;

class PluralIdentifyingRootField implements FieldInterface
{
    public function toFieldDefinition(array $config)
    {
        Config::validate($config, [
            'name' => Config::STRING,
            'argName' => Config::STRING | Config::REQUIRED,
            'inputType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'outputType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'resolveSingleInput' => Config::CALLBACK | Config::REQUIRED,
            'description' => Config::STRING,
        ]);

        $inputArgs = [
            $config['argName'] => [
                'type' => Type::nonNull(
                    Type::listOf(
                        Type::nonNull($config['inputType'])
                    )
                ),
            ],
        ];

        return [
            'name' => $config['name'],
            'description' => isset($config['description']) ? $config['description'] : null,
            'type' => Type::listOf($config['outputType']),
            'args' => $inputArgs,
            'resolve' => function ($obj, $args, $info) use ($config) {
                $inputs = $args[$config['argName']];

                $data = [];

                foreach ($inputs as $input) {
                    $data[$input] = is_callable($config['resolveSingleInput']) ?
                        call_user_func_array($config['resolveSingleInput'], [$input, $info]) :
                        null;
                }

                return $data;
            },
        ];
    }
}
