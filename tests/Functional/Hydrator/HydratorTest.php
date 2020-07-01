<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class HydratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'hydrator']);
    }

    /**
     * @test
     */
    public function simpleHydration(): void
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        $query = <<<QUERY
        mutation {
            createUser(input: {
                username: "murtukov"
                firstName: "Timur"
                lastName: "Murtukov"
            })
        }
        QUERY;

        $result = self::executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['noValidation']);
    }
}
