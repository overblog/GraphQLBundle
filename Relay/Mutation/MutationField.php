<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Mutation;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\FieldInterface;
use Overblog\GraphQLBundle\Definition\MergeFieldTrait;

class MutationField implements FieldInterface
{
    use MergeFieldTrait;

    public function toFieldDefinition(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name'                => Config::STRING | Config::REQUIRED,
            'mutateAndGetPayload' => Config::CALLBACK | Config::REQUIRED,
            'payloadType'         => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'inputType'           => Config::INPUT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'description'         => Config::STRING,
        ]);

        $name = $config['name'];

        $mutateAndGetPayload = $config['mutateAndGetPayload'];
        $description = isset($config['description']) ? $config['description'] : null;
        $payloadType = $config['payloadType'];
        $inputType = $config['inputType'];

        return [
            'name'        => $name,
            'description' => $description,
            'type'        => $payloadType,
            'args'        => [
                'input' => ['type' => Type::nonNull($inputType)],
            ],
            'resolve' => function ($_, $input, $info) use ($mutateAndGetPayload, $name) {
                $payload = $mutateAndGetPayload($input['input'], $info);
                $payload['clientMutationId'] = $input['input']['clientMutationId'];

                return $payload;
            },
        ];
    }
}
