<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition;
use PHPUnit\Framework\TestCase;

class PluralIdentifyingRootFieldDefinitionTest extends TestCase
{
    /** @var PluralIdentifyingRootFieldDefinition */
    private $definition;

    public function setUp(): void
    {
        $this->definition = new PluralIdentifyingRootFieldDefinition();
    }

    public function testUndefinedArgNameConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "argName" config is required.');
        $this->definition->toMappingDefinition([]);
    }

    public function testArgNameConfigSetButIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "argName" config is required.');
        $this->definition->toMappingDefinition(['argName' => 45]);
    }

    public function testUndefinedInputTypeConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "inputType" config is required.');
        $this->definition->toMappingDefinition(['argName' => 'username']);
    }

    public function testInputTypeConfigSetButIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "inputType" config is required.');
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 45]);
    }

    public function testUndefinedOutputTypeConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "outputType" config is required.');
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput']);
    }

    public function testOutputTypeConfigSetButIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A valid pluralIdentifyingRoot "outputType" config is required.');
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput', 'outputType' => 35]);
    }

    public function testUndefinedResolveSingleInputConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PluralIdentifyingRoot "resolveSingleInput" config is required.');
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput', 'outputType' => 'User']);
    }

    /**
     * @param mixed $resolveSingleInput
     * @dataProvider validConfigProvider
     */
    public function testValidConfig($resolveSingleInput, string $expectedResolveSingleInputCallbackArg): void
    {
        $config = [
            'argName' => 'username',
            'inputType' => 'UserInput',
            'outputType' => 'User',
            'resolveSingleInput' => $resolveSingleInput,
        ];

        $expected = [
            'type' => '[User]',
            'args' => ['username' => ['type' => '[UserInput!]!']],
            'resolve' => '@=resolver(\'relay_plural_identifying_field\', [args[\'username\'], context, info, resolveSingleInputCallback('.$expectedResolveSingleInputCallbackArg.')])',
        ];

        $this->assertSame($expected, $this->definition->toMappingDefinition($config));
    }

    public function validConfigProvider(): array
    {
        return [
            ['@=user.username', 'user.username'],
            [null, 'null'],
            [false, 'false'],
            [true, 'true'],
            [15, '15'],
            [['result' => 1], '{"result":1}'],
            [['result'], '["result"]'],
        ];
    }
}
