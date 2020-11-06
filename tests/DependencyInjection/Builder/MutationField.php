<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use function array_keys;
use function sprintf;

final class MutationField implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $name = $config['name'] ?? null;
        $resolver = $config['resolver'] ?? null;
        $inputFields = $config['inputFields'] ?? [];

        $successPayloadFields = $config['payloadFields'] ?? null;
        $failurePayloadFields = [
            '_error' => ['type' => 'String'],
        ];

        foreach (array_keys($inputFields) as $fieldName) {
            $failurePayloadFields[$fieldName] = ['type' => 'String'];
        }

        $payloadTypeName = $name.'Payload';
        $payloadSuccessTypeName = $name.'SuccessPayload';
        $payloadFailureTypeName = $name.'FailurePayload';
        $inputTypeName = $name.'Input';

        $field = [
            'type' => $payloadTypeName.'!',
            'resolve' => sprintf('@=mutation("%s", [args["input"]])', $resolver),
            'args' => [
                'input' => $inputTypeName.'!',
            ],
        ];

        $types = [
            $inputTypeName => [
                'type' => 'input-object',
                'config' => [
                    'fields' => $inputFields,
                ],
            ],
            $payloadTypeName => [
                'type' => 'union',
                'config' => [
                    'types' => [$payloadSuccessTypeName, $payloadFailureTypeName],
                    'resolveType' => sprintf(
                        '@=resolver("PayloadTypeResolver", [value, "%s", "%s"])',
                        $payloadSuccessTypeName,
                        $payloadFailureTypeName
                    ),
                ],
            ],
            $payloadSuccessTypeName => [
                'type' => 'object',
                'config' => [
                    'fields' => $successPayloadFields,
                ],
            ],
            $payloadFailureTypeName => [
                'type' => 'object',
                'config' => [
                    'fields' => $failurePayloadFields,
                ],
            ],
        ];

        return ['field' => $field, 'types' => $types];
    }
}
