<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\app\Mutation\SimpleMutationWithThunkFieldsMutation;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class AccessTest extends TestCase
{
    const USER_RYAN = 'ryan';
    const USER_ADMIN = 'admin';
    const ANONYMOUS_USER = null;

    private $userNameQuery = 'query MyQuery { user { name } }';

    private $userRolesQuery = 'query MyQuery { user { roles } }';

    private $userIsEnabledQuery = 'query MyQuery { user { isEnabled } }';

    private $userFriendsQuery = <<<EOF
query MyQuery {
  user {
    friends(first: 2) {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOF;

    private $simpleMutationWithThunkQuery = <<<EOF
mutation M {
  simpleMutationWithThunkFields(input: {inputData: %d, clientMutationId: "bac"}) {
    result
    clientMutationId
  }
}
EOF;

    public function testNotAuthenticatedUserAccessToUserName()
    {
        $expected = [
            'data' => [
                'user' => [
                    'name' => null,
                ],
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Access denied to this field.',
                        'locations' => [['line' => 1, 'column' => 24]],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->userNameQuery, $expected, static::ANONYMOUS_USER);
    }

    public function testFullyAuthenticatedUserAccessToUserName()
    {
        $expected = [
            'data' => [
                'user' => [
                    'name' => 'Dan',
                ],
            ],
        ];

        $this->assertResponse($this->userNameQuery, $expected, static::USER_RYAN);
    }

    public function testNotAuthenticatedUserAccessToUserRoles()
    {
        $this->assertResponse($this->userRolesQuery, $this->expectedFailedUserRoles(), static::ANONYMOUS_USER);
    }

    public function testAuthenticatedUserAccessToUserRolesWithoutEnoughRights()
    {
        $this->assertResponse($this->userRolesQuery, $this->expectedFailedUserRoles(), static::USER_RYAN);
    }

    public function testUserWithCorrectRightsAccessToUserRoles()
    {
        $expected = [
            'data' => [
                'user' => [
                    'roles' => ['ROLE_USER'],
                ],
            ],
        ];

        $this->assertResponse($this->userRolesQuery, $expected, static::USER_ADMIN);
    }

    public function testUserForbiddenField()
    {
        $expected = [
            'data' => [
                'user' => null,
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Access denied to this field.',
                        'locations' => [
                            [
                                'line' => 3,
                                'column' => 5,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $query = <<<EOF
query MyQuery {
  user {
    forbidden
  }
}
EOF;

        $this->assertResponse($query, $expected, static::USER_ADMIN);
    }

    public function testUserAccessToUserFriends()
    {
        $expected = [
            'data' => [
                'user' => [
                    'friends' => [
                        'edges' => [
                            ['node' => ['name' => 'Nick']],
                            ['node' => null],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsQuery, $expected, static::USER_ADMIN);
    }

    public function testMutationAllowedUser()
    {
        $result = 123;

        $expected = [
            'data' => [
                'simpleMutationWithThunkFields' => [
                    'result' => $result,
                    'clientMutationId' => 'bac',
                ],
            ],
        ];

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, $result), $expected, static::USER_ADMIN);
        $this->assertTrue(SimpleMutationWithThunkFieldsMutation::hasMutate(true));
    }

    public function testMutationAllowedButNoRightsToDisplayPayload()
    {
        $expected = [
            'data' => [
                'simpleMutationWithThunkFields' => [
                    'result' => null,
                    'clientMutationId' => 'bac',
                ],
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Access denied to this field.',
                        'locations' => [
                            [
                                'line' => 3,
                                'column' => 5,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, 321), $expected, static::USER_ADMIN);
        $this->assertTrue(SimpleMutationWithThunkFieldsMutation::hasMutate(true));
    }

    public function testMutationNotAllowedUser()
    {
        $expected = [
            'data' => [
                'simpleMutationWithThunkFields' => null,
            ],
            'errors' => [
                [
                    'message' => 'Access denied to this field.',
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, 123), $expected, static::USER_RYAN);
        $this->assertFalse(SimpleMutationWithThunkFieldsMutation::hasMutate(true));
    }

    private function expectedFailedUserRoles()
    {
        return [
            'data' => [
                'user' => [
                    'roles' => [0 => null],
                ],
            ],
        ];
    }

    private static function assertResponse($query, array $expected, $username)
    {
        $client = self::createClientAuthenticated($username);
        $client->request('GET', '/', ['query' => $query]);

        $result = $client->getResponse()->getContent();

        static::assertEquals($expected, json_decode($result, true), $result);

        return $client;
    }

    private static function createClientAuthenticated($username)
    {
        $client = static::createClient(['test_case' => 'access']);

        if ($username) {
            $client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => '123',
            ]);
        }

        return $client;
    }
}
