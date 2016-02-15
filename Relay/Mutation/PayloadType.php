<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\MergeFieldTrait;

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
            'description' => Config::STRING
        ]);

        $description = isset($config['description']) ? $config['description'] : null;
        $outputFields = isset($config['fields']) ? $config['fields'] : [];

        $augmentedOutputFields = $this->getFieldsWithDefaults(
            $outputFields,
            [
                'clientMutationId' => ['type' => Type::nonNull(Type::string())]
            ]
        );

        $name = str_replace('Payload', '', $config['name']);
        if (empty($name)) {
            $name = $config['name'];
        }

        parent::__construct([
            'name' => $name . 'Payload',
            'fields' => $augmentedOutputFields,
            'description' => $description
        ]);
    }
}
