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

    public function testNotAuthenticatedUserAccessToUserName()
    {
        $expected = [
            'data' => [
                'user' => [
                    'name' => null,
                ],
            ],
            'errors' => [
                [
                    'message' => 'Access denied to this field.',
                    'locations' => [['line' => 1, 'column' => 24]],
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

    public function testUserAccessToUserIsEnabledWithExpressionLanguageEvaluationFailed()
    {
        $expected = [
            'data' => [
                'user' => [
                    'isEnabled' => null,
                ],
            ],
            'errors' => [
                [
                    'message' => 'Access denied to this field.',
                    'locations' => [['line' => 1, 'column' => 24]],
                ],
            ],
        ];

        $this->assertResponse($this->userIsEnabledQuery, $expected, static::USER_ADMIN);
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
