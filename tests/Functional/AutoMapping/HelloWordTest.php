<?php

namespace Overblog\GraphQLBundle\Tests\Functional\AutoMapping;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class HelloWordTest extends TestCase
{
    protected function setUp()
    {
        static::bootKernel(['test_case' => 'autoMapping']);
    }

    public function testQuery()
    {
        $query = 'query { echo(message: "This is my message!") }';
        $expectedData = ['echo' => 'You said: This is my message!'];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testMutation()
    {
        $query = 'mutation { sum(x: 5, y: 15) }';
        $expectedData = ['sum' => '20'];

        $this->assertGraphQL($query, $expectedData);
    }
}
