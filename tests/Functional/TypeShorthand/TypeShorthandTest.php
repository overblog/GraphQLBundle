<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\AutoConfigure;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class TypeShorthandTest extends TestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'typeShorthand']);
    }

    public function testQuery(): void
    {
        $query = 'query { user(auth: {username: "bar", password: "baz"}) {username, address {street, zipcode}} }';
        $expectedData = ['user' => ['username' => 'bar', 'address' => ['street' => 'bar foo street', 'zipcode' => '12345']]];

        $this->assertGraphQL($query, $expectedData);
    }
}
