<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Definition\Builder;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Definition\Builder\TypeBuilder;
use Overblog\GraphQLBundle\Resolver\ConfigResolver;

class TypeBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TypeBuilder
     */
    private $typeBuilder;

    public function setUp()
    {
        $this->typeBuilder = new TypeBuilder(new ConfigResolver());
    }

    /**
     * @param $type
     * @param array $config
     * @param $expectedInstanceOf
     *
     * @dataProvider getCreateDataProvider
     */
    public function testCreate($type, array $config, $expectedInstanceOf)
    {
        $type = $this->typeBuilder->create($type, $config);

        $this->assertInstanceOf($expectedInstanceOf, $type);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Type "toto" is not managed.
     */
    public function testCreateInvalidType()
    {
        $this->typeBuilder->create('toto', []);
    }

    public function getCreateDataProvider()
    {
        return [
            [
                'object', ['name' => 'object'], 'GraphQL\\Type\\Definition\\ObjectType',
            ],
            [
                'enum', ['name' => 'enum'], 'GraphQL\\Type\\Definition\\EnumType',
            ],
            [
                'interface', ['name' => 'interface'], 'GraphQL\\Type\\Definition\\InterfaceType',
            ],
            [
                'union', ['name' => 'union', 'types' => [new ObjectType(['name' => 'toto'])]], 'GraphQL\\Type\\Definition\\UnionType',
            ],
            [
                'input-object', ['name' => 'input'], 'GraphQL\\Type\\Definition\\InputObjectType',
            ],
        ];
    }
}
