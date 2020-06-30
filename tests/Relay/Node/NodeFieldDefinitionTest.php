<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition;
use PHPUnit\Framework\TestCase;

class NodeFieldDefinitionTest extends TestCase
{
    private NodeFieldDefinition $definition;

    public function setUp(): void
    {
        $this->definition = new NodeFieldDefinition();
    }

    public function testUndefinedIdFetcherConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Node "idFetcher" config is invalid.');
        $this->definition->toMappingDefinition([]);
    }

    public function testIdFetcherConfigSetButIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Node "idFetcher" config is invalid.');
        $this->definition->toMappingDefinition(['idFetcher' => 45]);
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testValidConfig(string $idFetcher, string $idFetcherCallbackArg, string $nodeInterfaceType = 'node'): void
    {
        $config = [
            'idFetcher' => $idFetcher,
            'inputType' => 'UserInput',
            'nodeInterfaceType' => $nodeInterfaceType,
        ];

        $expected = [
            'description' => 'Fetches an object given its ID',
            'type' => $nodeInterfaceType,
            'args' => ['id' => ['type' => 'ID!', 'description' => 'The ID of an object']],
            'resolve' => '@=resolver(\'relay_node_field\', [args, context, info, idFetcherCallback('.$idFetcherCallbackArg.')])',
        ];

        $this->assertSame($expected, $this->definition->toMappingDefinition($config));
    }

    public function validConfigProvider(): array
    {
        return [
            ['@=user.username', 'user.username'],
            ['toto', 'toto'],
            ['50', '50'],
            ['@=user.id', 'user.id', 'NodeInterface'],
        ];
    }
}
