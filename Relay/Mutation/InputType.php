<?php

namespace Overblog\GraphBundle\Relay\Mutation;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphBundle\Definition\MergeFieldTrait;

class InputType extends InputObjectType
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
        $inputFields = isset($config['fields']) ? $config['fields'] : [];

        $augmentedInputFields = $this->getFieldsWithDefaults(
            $inputFields,
            [
                'clientMutationId' => ['type' => Type::nonNull(Type::string())]
            ]
        );

        $name = str_replace('Input', '', $config['name']);
        if (empty($name)) {
            $name = $config['name'];
        }

        parent::__construct([
            'name' => $name . 'Input',
            'fields' => $augmentedInputFields,
            'description' => $description
        ]);
    }
}
