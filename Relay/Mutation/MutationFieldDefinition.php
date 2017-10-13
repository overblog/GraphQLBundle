<?php

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\GraphQL\Relay\Mutation\MutationFieldResolver;

final class MutationFieldDefinition implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        if (!array_key_exists('mutateAndGetPayload', $config)) {
            throw new \InvalidArgumentException('Mutation "mutateAndGetPayload" config is required.');
        }

        $mutateAndGetPayload = $this->cleanMutateAndGetPayload($config['mutateAndGetPayload']);
        $payloadType = isset($config['payloadType']) && is_string($config['payloadType']) ? $config['payloadType'] : null;
        $inputType = isset($config['inputType']) && is_string($config['inputType']) ? $config['inputType'].'!' : null;
        $resolver = addslashes(MutationFieldResolver::class);

        return [
            'type' => $payloadType,
            'args' => [
                'input' => ['type' => $inputType],
            ],
            'resolve' => "@=resolver('$resolver', [args, context, info, mutateAndGetPayloadCallback($mutateAndGetPayload)])",
        ];
    }

    private function cleanMutateAndGetPayload($mutateAndGetPayload)
    {
        if (is_string($mutateAndGetPayload) && 0 === strpos($mutateAndGetPayload, '@=')) {
            $cleanMutateAndGetPayload = substr($mutateAndGetPayload, 2);
        } else {
            $cleanMutateAndGetPayload = json_encode($mutateAndGetPayload);
        }

        return $cleanMutateAndGetPayload;
    }
}
