<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Mutation;

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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mutation "mutateAndGetPayload" config is required.
     *
     * @dataProvider undefinedMutateAndGetPayloadProvider
     */
    public function testUndefinedMutateAndGetPayloadConfig(array $config): void
    {
        $this->definition->toMappingDefinition($config);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot parse "mutateAndGetPayload" configuration string.
     */
    public function testInvalidMutateAndGetPayloadString(): void
    {
        $this->definition->toMappingDefinition(['mutateAndGetPayload' => 'Some invalid string']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid format for "mutateAndGetPayload" configuration.
     */
    public function testInvalidMutateAndGetPayloadFormat(): void
    {
        $this->definition->toMappingDefinition(['mutateAndGetPayload' => 123]);
    }

    public function undefinedMutateAndGetPayloadProvider(): array
    {
        return [
            [[]],
            [['mutateAndGetPayload' => null]],
        ];
    }
}
