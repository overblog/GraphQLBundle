<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Mutation;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use PHPUnit\Framework\TestCase;

class MutationFieldDefinitionTest extends TestCase
{
    /** @var MutationFieldDefinition */
    private $definition;

    public function setUp(): void
    {
        $this->definition = new MutationFieldDefinition();
    }

    /**
     * @dataProvider validConfigurationProvider
     */
    public function testToMappingDefinition(array $config, array $expectedMapping): void
    {
        self::assertEquals(
            $expectedMapping,
            $this->definition->toMappingDefinition($config)
        );
    }

    /**
     * @dataProvider undefinedMutateAndGetPayloadProvider
     */
    public function testUndefinedMutateAndGetPayloadConfig(array $config): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mutation "mutateAndGetPayload" config is required.');
        $this->definition->toMappingDefinition($config);
    }

    public function testInvalidMutateAndGetPayloadString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse "mutateAndGetPayload" configuration string.');
        $this->definition->toMappingDefinition(['mutateAndGetPayload' => 'Some invalid string']);
    }

    public function testInvalidMutateAndGetPayloadFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid format for "mutateAndGetPayload" configuration.');
        $this->definition->toMappingDefinition(['mutateAndGetPayload' => 123]);
    }

    public function validConfigurationProvider(): array
    {
        return [
            'types not string return null' => [[
                'payloadType' => 123,
                'inputType' => [],
                'mutateAndGetPayload' => '@=foobar',
            ], [
                'type' => null,
                'args' => [
                    'input' => [
                        'type' => null,
                    ],
                ],
                'resolve' => '@=resolver(\'relay_mutation_field\', [args, context, info, mutateAndGetPayloadCallback(foobar)])',
            ]],
            'types set as string return expected type string' => [[
                'payloadType' => 'foo',
                'inputType' => 'bar',
                'mutateAndGetPayload' => '@=foobar',
            ], [
                'type' => 'foo',
                'args' => [
                    'input' => [
                        'type' => 'bar!',
                    ],
                ],
                'resolve' => '@=resolver(\'relay_mutation_field\', [args, context, info, mutateAndGetPayloadCallback(foobar)])',
            ]],
        ];
    }

    public function undefinedMutateAndGetPayloadProvider(): array
    {
        return [
            [[]],
            [['mutateAndGetPayload' => null]],
        ];
    }
}
