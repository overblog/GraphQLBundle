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
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\FieldDefinition;
use Overblog\GraphQLBundle\Definition\MergeFieldTrait;
use Overblog\GraphQLBundle\Definition\ObjectType;
use Overblog\GraphQLBundle\Definition\Type;

class PayloadType extends ObjectType
{
    use MergeFieldTrait;

    public function __construct(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'fields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME
            ),
            'description' => Config::STRING,
        ]);

        $description = isset($config['description']) ? $config['description'] : null;
        $outputFields = isset($config['fields']) ? $config['fields'] : [];

        $augmentedOutputFields = $this->getFieldsWithDefaults(
            $outputFields,
            [
                'clientMutationId' => ['type' => Type::nonNull(Type::string())],
            ]
        );

        $name = str_replace('Payload', '', $config['name']);

        parent::__construct([
            'name' => $name.'Payload',
            'fields' => $augmentedOutputFields,
            'description' => $description,
        ]);
    }
}
