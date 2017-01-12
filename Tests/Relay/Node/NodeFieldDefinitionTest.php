<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition;

class NodeFieldDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeFieldDefinition
     */
    private $definition;

    public function setUp()
    {
        $this->definition = new NodeFieldDefinition();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Node "idFetcher" config is invalid.
     */
    public function testUndefinedIdFetcherConfig()
    {
        $this->definition->toMappingDefinition([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Node "idFetcher" config is invalid.
     */
    public function testIdFetcherConfigSetButIsNotString()
    {
        $this->definition->toMappingDefinition(['idFetcher' => 45]);
    }

    /**
     * @dataProvider validConfigProvider
     *
     * @param $idFetcher
     * @param $idFetcherCallbackArg
     * @param $nodeInterfaceType
     */
    public function testValidConfig($idFetcher, $idFetcherCallbackArg, $nodeInterfaceType = 'node')
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
            'resolve' => '@=resolver(\'relay_node_field\', [args, idFetcherCallback('.$idFetcherCallbackArg.')])',
        ];

        $this->assertEquals($expected, $this->definition->toMappingDefinition($config));
    }

    public function validConfigProvider()
    {
        return [
            ['@=user.username', 'user.username'],
            ['toto', 'toto'],
            ['50', '50'],
            ['@=user.id', 'user.id', 'NodeInterface'],
        ];
    }
}
