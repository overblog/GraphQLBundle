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
    private const VALID_QUERY = '
        mutation {
            createUser(
                username: "murtukov"
                password: "guitar12"
                passwordRepeat: "guitar12"
                birthday: "1990-03-17"
                emails: ["murtukov@gmail.com", "equilibrium.90@mail.ru", "maxmustermann@berlin.de"]
                about: "It is me, Jake"
                address: {
                    street: "Albrechtstr."
                    city: "Moscow"
                    zipCode: 368500
                    residents: ["murtukov", "godfri"]
                },
                workPeriod: {
                    startDate: "2016-01-01"
                    endDate: "2019-07-14"
                }
                groups: []
                extraConfig: "{\"id\":15}"
          )
        }
    ';

    private const INVALID_QUERY = '
        mutation {
            createUser(
                username: "mur"
                password: "123"
                passwordRepeat: ""
                birthday: "invalid date"
                emails: ["same", "same"]
                about: "It is me, Jake"
                address: {
                    street: "Albrechtstr."
                    city: "Moscow"
                    zipCode: 368500
                    residents: ["murtukov", "godfri"]
                },
                workPeriod: {
                    startDate: "2016-01-01"
                    endDate: "2019-07-14"
                }
                groups: []
                extraConfig: "{\"id\":15}"
          )
        }
    ';

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'validator']);
    }

    public function testValidationPasses()
    {
        $result = $this->executeGraphQLRequest(self::VALID_QUERY);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['createUser']);
    }

    // TODO: finish this assertion
    public function testValidationFails()
    {
        $this->assertTrue(true);
    }
}
