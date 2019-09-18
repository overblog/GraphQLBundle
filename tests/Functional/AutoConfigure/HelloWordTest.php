<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\AutoConfigure;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * @group legacy
 */
class HelloWordTest extends TestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'autoConfigure']);
    }

    public function testQuery(): void
    {
        $query = 'query { echo(message: "This is my message!") }';
        $expectedData = ['echo' => 'You said: This is my message!'];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testMutation(): void
    {
        $query = 'mutation { sum(x: 5, y: 15) }';
        $expectedData = ['sum' => 20];

        $this->assertGraphQL($query, $expectedData);
    }
}
