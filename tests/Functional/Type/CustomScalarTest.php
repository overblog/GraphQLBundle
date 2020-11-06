<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Type;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class CustomScalarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'customScalar']);
    }

    public function testDateTimeTypeSerialize(): void
    {
        $query = '{ dateTime }';
        $expected = [
            'dateTime' => '2016-11-28 12:00:00',
        ];
        $this->assertGraphQL($query, $expected);
    }

    public function testDateTimeTypeParseValue(): void
    {
        $query = '{ dateTime(dateTime: "2016-01-18 23:00:00") }';
        $expected = [
            'dateTime' => '2016-01-18 23:00:00',
        ];
        $this->assertGraphQL($query, $expected);
    }

    public function testDateTimeTypeDescription(): void
    {
        // @phpstan-ignore-next-line
        $dateTimeType = static::$kernel->getContainer()->get('overblog_graphql.type_resolver')->resolve('DateTime');
        $this->assertSame('The DateTime type', $dateTimeType->description);
    }
}
