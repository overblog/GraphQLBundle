<?php

namespace Overblog\GraphQLBundle\Tests\Functional\DefaultValue;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class DefaultValueTest extends TestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'defaultValue']);
    }

    public function testArgDefaultValue(): void
    {
        $query = 'mutation { echo }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertSame('foo', $result['data']['echo']);
    }

    public function testNullableDefaultValue(): void
    {
        $query = 'mutation { isStringNull }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['isStringNull']);
    }

    public function testArgDefaultValueWithInput(): void
    {
        $query = 'mutation { echoUsingInput(input: {}) }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertSame('foo', $result['data']['echoUsingInput']);
    }

    public function testNullableDefaultValueWithInput(): void
    {
        $query = 'mutation { isStringNullUsingInput(input: {}) }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['isStringNullUsingInput']);
    }

    public function testArgDefaultValueArgWithInput(): void
    {
        $query = 'mutation { echoUsingInputWithDefaultArg }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertSame('bar', $result['data']['echoUsingInputWithDefaultArg']);
    }
}
