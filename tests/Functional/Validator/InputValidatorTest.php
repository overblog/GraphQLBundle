<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class InputValidatorTest
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class InputValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'validator']);
    }

    public function testSimpleValidationPasses()
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

    public function testSimpleValidationFails()
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

    public function testLinkedConstraintsValidationPasses()
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

    public function testLinkedConstraintsValidationFails()
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

    public function testCollectionValidationPasses()
    {
        $query = '
            mutation {
                collectionValidation(
                    emails: ["murtukov@gmail.com", "equilibrium.90@mail.ru", "maxmustermann@berlin.de"]
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['collectionValidation']);
    }

    public function testCollectionValidationFails()
    {
        $query = '
            mutation {
                collectionValidation(
                    emails: ["nonUniqueString", "nonUniqueString"]
                )
            }
        ';

        $result = $this->executeGraphQLRequest($query);

        $this->assertSame(ExpectedErrors::COLLECTION, $result['errors'][0]);
        $this->assertNull($result['data']['collectionValidation']);
    }

    public function testCascadeValidationWithGroupsPasses()
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

    public function testCascadeValidationWithGroupsFails()
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

        $this->assertSame(ExpectedErrors::CASCADE, $result['errors'][0]);
        $this->assertNull($result['data']['cascadeValidationWithGroups']);
    }
}
