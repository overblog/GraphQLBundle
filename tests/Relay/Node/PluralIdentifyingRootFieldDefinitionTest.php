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

use Overblog\GraphQLBundle\GraphQL\Relay\Node\PluralIdentifyingRootFieldResolver;
use Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition;
use PHPUnit\Framework\TestCase;

class PluralIdentifyingRootFieldDefinitionTest extends TestCase
{
    /**
     * @var PluralIdentifyingRootFieldDefinition
     */
    private $definition;

    public function setUp()
    {
        $this->definition = new PluralIdentifyingRootFieldDefinition();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "argName" config is required.
     */
    public function testUndefinedArgNameConfig()
    {
        $this->definition->toMappingDefinition([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "argName" config is required.
     */
    public function testArgNameConfigSetButIsNotString()
    {
        $this->definition->toMappingDefinition(['argName' => 45]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "inputType" config is required.
     */
    public function testUndefinedInputTypeConfig()
    {
        $this->definition->toMappingDefinition(['argName' => 'username']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "inputType" config is required.
     */
    public function testInputTypeConfigSetButIsNotString()
    {
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 45]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "outputType" config is required.
     */
    public function testUndefinedOutputTypeConfig()
    {
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A valid pluralIdentifyingRoot "outputType" config is required.
     */
    public function testOutputTypeConfigSetButIsNotString()
    {
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput', 'outputType' => 35]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage PluralIdentifyingRoot "resolveSingleInput" config is required.
     */
    public function testUndefinedResolveSingleInputConfig()
    {
        $this->definition->toMappingDefinition(['argName' => 'username', 'inputType' => 'UserInput', 'outputType' => 'User']);
    }

    /**
     * @param $resolveSingleInput
     * @param $expectedResolveSingleInputCallbackArg
     *
     * @dataProvider validConfigProvider
     */
    public function testValidConfig($resolveSingleInput, $expectedResolveSingleInputCallbackArg)
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
            'resolve' => '@=resolver(\''.addslashes(PluralIdentifyingRootFieldResolver::class).'\', [args[\'username\'], context, info, resolveSingleInputCallback('.$expectedResolveSingleInputCallbackArg.')])',
        ];

        $this->assertEquals($expected, $this->definition->toMappingDefinition($config));
    }

    public function validConfigProvider()
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
