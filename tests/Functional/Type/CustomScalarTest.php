<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class CustomScalarTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::createAndBootKernel(['test_case' => 'customScalar']);
    }

    public function testDateTimeTypeSerialize()
    {
        $query = '{ dateTime }';
        $expected = [
            'dateTime' => '2016-11-28 12:00:00',
        ];
        $this->assertGraphQL($query, $expected);
    }

    public function testDateTimeTypeParseValue()
    {
        $query = '{ dateTime(dateTime: "2016-01-18 23:00:00") }';
        $expected = [
            'dateTime' => '2016-01-18 23:00:00',
        ];
        $this->assertGraphQL($query, $expected);
    }
}
