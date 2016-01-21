<?php

namespace Overblog\GraphBundle\Relay\Connection\Mutation;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Utils;
use Overblog\GraphBundle\Definition\FieldInterface;
use Overblog\GraphBundle\Definition\MergeFieldTrait;

class MutationField implements FieldInterface
{
    use MergeFieldTrait;

    private $name;

    private $description;

    private $type;

    private $args;

    private $resolve;

    public function __construct(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'inputFields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME
            ),
            'outputFields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME
            ),
            'mutateAndGetPayload' => Config::CALLBACK | Config::REQUIRED,
            'description' => Config::STRING
        ]);

        $name = str_replace('Mutation', '', $config['name']);
        if (empty($name)) {
            $name = $config['name'];
        }
        $name = $name . 'Mutation';
        $inputFields = $config['inputFields'];
        $outputFields = $config['outputFields'];
        $mutateAndGetPayload = $config['mutateAndGetPayload'];
        $description = isset($config['description']) ? $config['description'] : null;

        $augmentedInputFields = $this->getFieldsWithDefaults(
            $inputFields,
            [
                'clientMutationId' => ['type' => Type::nonNull(Type::string())]
            ]
        );

        $augmentedOutputFields = $this->getFieldsWithDefaults(
            $outputFields,
            [
                'clientMutationId' => ['type' => Type::nonNull(Type::string())]
            ]
        );

        $outputType = new ObjectType([
            'name' => $name . 'Payload',
            'fields' => $augmentedOutputFields,
        ]);

        $inputType = new InputObjectType([
            'name' => $name . 'Input',
            'fields' => $augmentedInputFields(), // TODO(mcg-web) work on a PR to accept array fields or closure that return array fields
        ]);

        $this->name = $name;
        $this->description = $description;

        $this->type = $outputType;
        $this->args = [
            'input' => ['type' =>  Type::nonNull($inputType)]
        ];
        $this->resolve = function($_, $input, $info) use ($mutateAndGetPayload) {
            if (empty($input['input'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Field \"%s\" argument \"input\" of type \"%sInput!\" is required but not provided.",
                        $this->name,
                        $this->name
                    )
                );
            }

            $payload = $mutateAndGetPayload($_, $input, $info);
            $payload['clientMutationId'] = $input['input']['clientMutationId'];

            return $payload;
        };
    }

    /**
     * @return array
     */
    public function toFieldsDefinition()
    {
        return get_object_vars($this);
    }
}
