<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Definition;

use Overblog\GraphQLBundle\Definition\MergeFieldTrait;

class MergeFieldTraitTest extends \PHPUnit_Framework_TestCase
{
    use MergeFieldTrait;

    /**
     * @param $fields
     * @param array $defaultFields
     * @param $forceArray
     * @param $expectedFields
     *
     * @dataProvider getFieldsDataProvider
     */
    public function testGetFieldsWithDefaults($fields, array $defaultFields, $forceArray, $expectedFields)
    {
        $this->assertEquals($expectedFields, $this->getFieldsWithDefaults($fields, $defaultFields, $forceArray));
    }

    public function getFieldsDataProvider()
    {
        return [
            [
                ['toto', 'tata', 'titi'],
                ['foo', 'bar'],
                true,
                ['toto', 'tata', 'titi', 'foo', 'bar'],
            ],
            [
                [],
                ['foo'],
                true,
                ['foo'],
            ],
            [
                function () {
                    return ['test'];
                },
                ['bar'],
                true,
                ['test', 'bar'],
            ],
            [
                'toto',
                ['tata'],
                true,
                ['toto', 'tata'],
            ],
        ];
    }

    public function testGetFieldsWithDefaultsForceArray()
    {
        $fields = $this->getFieldsWithDefaults(function () { return ['bar']; }, ['toto'], false);
        $this->assertInstanceOf('Closure', $fields);
        $this->assertEquals(['bar', 'toto'], $fields());
    }
}
