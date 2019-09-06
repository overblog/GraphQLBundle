<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class InputValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'validator']);
    }

    public function testNoValidation(): void
    {
        $query = 'mutation { noValidation(username: "test") }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['noValidation']);
    }

    public function testSimpleValidationPasses(): void
    {
        $query = '
            mutation {
                simpleValidation(username: "Andrew")
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['simpleValidation']);
    }

    public function testSimpleValidationFails(): void
    {
        $query = '
            mutation {
                simpleValidation(username: "Tim")
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::SIMPLE_VALIDATION, $result['errors'][0]);
        $this->assertNull($result['data']['simpleValidation']);
    }

    public function testLinkedConstraintsValidationPasses(): void
    {
        $query = '
            mutation {
                linkedConstraintsValidation(
                    string1: "Lorem Ipsum"
                    string2: "Dolor Sit Amet"
                    string3: "{\"text\":\"Lorem Ipsum\"}"
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['linkedConstraintsValidation']);
    }

    public function testLinkedConstraintsValidationFails(): void
    {
        $query = '
            mutation {
                linkedConstraintsValidation(
                    string1: "Dolor Sit Amet"
                    string2: "Lorem Ipsum"
                    string3: "Lorem Ipsum"
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::LINKED_CONSTRAINTS, $result['errors'][0]);
        $this->assertNull($result['data']['linkedConstraintsValidation']);
    }

    public function testCollectionValidationPasses(): void
    {
        $query = '
            mutation {
                collectionValidation(
                    addresses: [{
                        city: "Berlin", 
                        street: "Brettnacher-Str. 14a", 
                        zipCode: 10546, 
                        period: {
                            startDate: "2016-01-01", 
                            endDate: "2019-07-14"
                        }
                    }]
                    emails: ["murtukov@gmail.com", "equilibrium.90@mail.ru", "maxmustermann@berlin.de"]
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['collectionValidation']);
    }

    public function testCollectionValidationFails(): void
    {
        $query = '
            mutation {
                collectionValidation(
                    addresses: [{
                        city: "Moscow", 
                        street: "ul. Lazo", 
                        zipCode: -15, 
                        period: {
                            startDate: "2020-01-01", 
                            endDate: "2019-07-14"
                        }
                    }]
                    emails: ["nonUniqueString", "nonUniqueString"]
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::COLLECTION, $result['errors'][0]);
        $this->assertNull($result['data']['collectionValidation']);
    }

    public function testCascadeValidationWithGroupsPasses(): void
    {
        $query = '
            mutation {
                cascadeValidationWithGroups(
                    groups: ["Default", "Address", "Period", "group1", "group2"]
                    birthdate: {
                        day: 15
                        month: 315
                        year: 3146
                    }
                    address: {
                        street: "Washington Street"
                        city: "New York"
                        zipCode: 10006
                        period: {
                            startDate: "2016-01-01"
                            endDate: "2019-07-14"
                        }
                    }
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['cascadeValidationWithGroups']);
    }

    public function testCascadeValidationWithGroupsFails(): void
    {
        $query = '
            mutation {
                cascadeValidationWithGroups(
                    groups: ["Default", "Address", "Period", "group1", "group2"]
                    birthdate: {
                        day: 699
                        month: 315
                        year: 3146
                    }
                    address: {
                        street: "ul. Lazo"
                        city: "Moscow"
                        zipCode: -215
                        period: {
                            startDate: "2020-01-01"
                            endDate: "2019-07-14"
                        }
                    }
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::CASCADE_WITH_GROUPS, $result['errors'][0]);
        $this->assertNull($result['data']['cascadeValidationWithGroups']);
    }

    public function testUserPasswordMatches()
    {
        $query = '
            mutation {
                userPasswordValidation(oldPassword: "123")
            }
        ';

        $jsonString = $this->query($query, 'Ryan', 'validator')->getResponse()->getContent();

        $response = \json_decode($jsonString, true);

        $this->assertTrue(empty($response['errors']));
        $this->assertTrue($response['data']['userPasswordValidation']);
    }

    public function testExpressionVariablesAccessible()
    {
        $query = 'mutation { expressionVariablesValidation(username: "test") }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['expressionVariablesValidation']);
    }
}
