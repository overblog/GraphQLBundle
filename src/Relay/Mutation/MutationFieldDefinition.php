<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Relay\Mutation;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;
use function strpos;
use function substr;

final class MutationFieldDefinition implements MappingInterface
{
    private const KEY_MUTATE_GET_PAYLOAD = 'mutateAndGetPayload';

    public function toMappingDefinition(array $config): array
    {
        $mutateAndGetPayload = $this->cleanMutateAndGetPayload($config);

        return [
            'type' => $this->extractPayloadType($config),
            'args' => [
                'input' => ['type' => $this->extractInputType($config)],
            ],
            'resolve' => "@=resolver('relay_mutation_field', [args, context, info, mutateAndGetPayloadCallback($mutateAndGetPayload)])",
        ];
    }

    private function cleanMutateAndGetPayload(array $config): string
    {
        $mutateAndGetPayload = $config[self::KEY_MUTATE_GET_PAYLOAD] ?? null;
        $this->ensureValidMutateAndGetPayloadConfiguration($mutateAndGetPayload);

        if (is_string($mutateAndGetPayload)) {
            return substr($mutateAndGetPayload, 2);
        }

        return json_encode($mutateAndGetPayload);
    }

    /**
     * @param mixed $mutateAndGetPayload
     *
     * @throws InvalidArgumentException
     */
    private function ensureValidMutateAndGetPayloadConfiguration($mutateAndGetPayload): void
    {
        if (is_string($mutateAndGetPayload) && 0 === strpos($mutateAndGetPayload, '@=')) {
            return;
        }

        if (null === $mutateAndGetPayload) {
            throw new InvalidArgumentException(sprintf('Mutation "%s" config is required.', self::KEY_MUTATE_GET_PAYLOAD));
        }

        if (is_string($mutateAndGetPayload)) {
            throw new InvalidArgumentException(sprintf('Cannot parse "%s" configuration string.', self::KEY_MUTATE_GET_PAYLOAD));
        }

        if (!is_array($mutateAndGetPayload)) {
            throw new InvalidArgumentException(sprintf('Invalid format for "%s" configuration.', self::KEY_MUTATE_GET_PAYLOAD));
        }
    }

    private function extractPayloadType(array $config): ?string
    {
        return isset($config['payloadType']) && is_string($config['payloadType']) ? $config['payloadType'] : null;
    }

    private function extractInputType(array $config): ?string
    {
        return isset($config['inputType']) && is_string($config['inputType']) ? $config['inputType'].'!' : null;
    }
}
