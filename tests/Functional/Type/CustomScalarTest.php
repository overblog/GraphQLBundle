<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Type;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class CustomScalarTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'customScalar']);
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

    public function testDateTimeTypeDescription()
    {
        $dateTimeType = static::$kernel->getContainer()->get('overblog_graphql.type_resolver')->resolve('DateTime');
        $this->assertEquals('The DateTime type', $dateTimeType->description);
    }
}
