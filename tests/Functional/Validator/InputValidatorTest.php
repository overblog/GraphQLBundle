<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use function class_exists;
use function json_decode;

class InputValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Symfony\\Component\\Validator\\Validation')) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
        static::bootKernel(['test_case' => 'validator']);
    }

    public function testNoValidation(): void
    {
        $query = 'mutation { noValidation(username: "test") }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['noValidation']);
    }

    /**
     * @group legacy
     */
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

    /**
     * @group legacy
     */
    public function testSimpleValidationFails(): void
    {
        $query = '
            mutation {
                simpleValidation(username: "Tim")
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::simpleValidation('simpleValidation'), $result['errors'][0]);
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

    /**
     * @group legacy
     */
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

    /**
     * @group legacy
     */
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

    /**
     * @group legacy
     */
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

    /**
     * @group legacy
     */
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

        $this->assertSame(ExpectedErrors::cascadeWithGroups('cascadeValidationWithGroups'), $result['errors'][0]);
        $this->assertNull($result['data']['cascadeValidationWithGroups']);
    }

    public function testUserPasswordMatches(): void
    {
        $query = '
            mutation {
                userPasswordValidation(oldPassword: "123")
            }
        ';

        /** @var string $jsonString */
        $jsonString = $this->query($query, 'Ryan', 'validator')->getResponse()->getContent();

        $response = json_decode($jsonString, true);

        $this->assertTrue(empty($response['errors']));
        $this->assertTrue($response['data']['userPasswordValidation']);
    }

    public function testExpressionVariablesAccessible(): void
    {
        $query = 'mutation { expressionVariablesValidation(username: "test") }';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['expressionVariablesValidation']);
    }

    /**
     * @group legacy
     */
    public function testAutoValidationAutoThrowPasses(): void
    {
        $query = '
            mutation {
                autoValidationAutoThrow(username: "Andrew")
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['autoValidationAutoThrow']);
    }

    /**
     * @group legacy
     */
    public function testAutoValidationAutoThrowFails(): void
    {
        $query = '
            mutation {
                autoValidationAutoThrow(username: "Tim")
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::simpleValidation('autoValidationAutoThrow'), $result['errors'][0]);
        $this->assertNull($result['data']['autoValidationAutoThrow']);
    }

    /**
     * @group legacy
     * Checks if the injected variable `errors` contains 0 violations.
     */
    public function testAutoValidationNoThrowNoErrors(): void
    {
        $query = 'mutation { autoValidationNoThrow(username: "Andrew") }';
        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue(false === $result['data']['autoValidationNoThrow']);
    }

    /**
     * @group legacy
     * Checks if the injected variable `errors` contains exactly 1 violation.
     */
    public function testAutoValidationNoThrowHasErrors(): void
    {
        $query = 'mutation { autoValidationNoThrow(username: "Tim") }';
        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue(true === $result['data']['autoValidationNoThrow']);
    }

    /**
     * @group legacy
     */
    public function testAutoValidationAutoThrowWithGroupsPasses(): void
    {
        $query = '
            mutation {
                autoValidationAutoThrowWithGroups(
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
        $this->assertTrue($result['data']['autoValidationAutoThrowWithGroups']);
    }

    /**
     * @group legacy
     */
    public function testAutoValidationAutoThrowWithGroupsFails(): void
    {
        $query = '
            mutation {
                autoValidationAutoThrowWithGroups(
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

        $this->assertSame(ExpectedErrors::cascadeWithGroups('autoValidationAutoThrowWithGroups'), $result['errors'][0]);
        $this->assertNull($result['data']['autoValidationAutoThrowWithGroups']);
    }
}
