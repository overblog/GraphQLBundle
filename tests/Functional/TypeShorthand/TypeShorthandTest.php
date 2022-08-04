<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\TypeShorthand;

use Doctrine\Common\Annotations\Reader;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Validator\Validation;

class TypeShorthandTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Validation::class)) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
        if (!interface_exists(Reader::class)) {
            $this->markTestSkipped('Symfony validator component requires doctrine/annotations but it is not installed');
        }
        static::bootKernel(['test_case' => 'typeShorthand']);
    }

    public function testQuery(): void
    {
        $query = 'query { user(auth: {username: "bar", password: "baz"}) {username, address {street, zipcode}} }';
        $expectedData = ['user' => ['username' => 'bar', 'address' => ['street' => 'bar foo street', 'zipcode' => '12345']]];

        static::assertGraphQL($query, $expectedData);
    }
}
