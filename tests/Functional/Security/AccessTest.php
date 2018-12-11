<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\App\Mutation\SimpleMutationWithThunkFieldsMutation;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\HttpKernel\Kernel;

class AccessTest extends TestCase
{
    /** @var \Closure */
    private $loader;

    private $userNameQuery = 'query { user { name } }';

    private $userRolesQuery = 'query { user { roles } }';

    private $userIsEnabledQuery = 'query ($hasAccess: Boolean = true) { user { isEnabled(hasAccess: $hasAccess) } }';

    private $userFriendsQuery = <<<'EOF'
query {
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

    private $simpleMutationWithThunkQuery = <<<'EOF'
mutation M {
  simpleMutationWithThunkFields(input: {inputData: %d, clientMutationId: "bac"}) {
    result
    clientMutationId
  }
}
EOF;

    public function setUp()
    {
        parent::setUp();
        // load types
        $this->loader = function ($class) {
            if (\preg_match('@^'.\preg_quote('Overblog\GraphQLBundle\Access\__DEFINITIONS__\\').'(.*)$@', $class, $matches)) {
                $file = '/tmp/OverblogGraphQLBundle/'.Kernel::VERSION.'/access/cache/testaccess/overblog/graphql-bundle/__definitions__/'.$matches[1].'.php';
                if (\file_exists($file)) {
                    require $file;
                }
            }
        };
        \spl_autoload_register($this->loader);
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Class 'Overblog\GraphQLBundle\Access\__DEFINITIONS__\RootQueryType' not found
     * @requires PHP 7
     */
    public function testCustomClassLoaderNotRegister()
    {
        \spl_autoload_unregister($this->loader);
        $this->assertResponse($this->userNameQuery, [], static::ANONYMOUS_USER, 'access');
    }

    public function testNotAuthenticatedUserAccessAsPromisedFulfilledTrue()
    {
        $this->assertResponse(
            $this->userIsEnabledQuery,
            ['data' => ['user' => ['isEnabled' => true]]],
            static::ANONYMOUS_USER,
            'access'
        );
    }

    public function testNotAuthenticatedUserAccessAsPromisedFulfilledFalse()
    {
        $this->assertResponse(
            $this->userIsEnabledQuery,
            [
                'data' => [
                    'user' => [
                        'isEnabled' => null,
                    ],
                ],
                'extensions' => [
                    'warnings' => [
                        [
                            'message' => 'Access denied to this field.',
                            'category' => 'user',
                            'locations' => [['line' => 1, 'column' => 45]],
                            'path' => ['user', 'isEnabled'],
                        ],
                    ],
                ],
            ],
            static::ANONYMOUS_USER,
            'access',
            '',
            ['hasAccess' => false]
        );
    }

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
                        'category' => 'user',
                        'locations' => [['line' => 1, 'column' => 16]],
                        'path' => ['user', 'name'],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->userNameQuery, $expected, static::ANONYMOUS_USER, 'access');
    }

    public function testNonAuthenticatedUserAccessSecuredFieldWhichInitiallyResolvesToArray()
    {
        $expected = [
            'data' => [
                'youShallNotSeeThisUnauthenticated' => null,
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Access denied to this field.',
                        'category' => 'user',
                        'locations' => [
                            [
                                'line' => 2,
                                'column' => 3,
                            ],
                        ],
                        'path' => ['youShallNotSeeThisUnauthenticated'],
                    ],
                ],
            ],
        ];
        $query = <<<'EOF'
{
  youShallNotSeeThisUnauthenticated {
    secretValue
    youAreAuthenticated
  }
}
EOF;
        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'access');
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

        $this->assertResponse($this->userNameQuery, $expected, static::USER_RYAN, 'access');
    }

    public function testNotAuthenticatedUserAccessToUserRoles()
    {
        $this->assertResponse($this->userRolesQuery, $this->expectedFailedUserRoles(), static::ANONYMOUS_USER, 'access');
    }

    public function testAuthenticatedUserAccessToUserRolesWithoutEnoughRights()
    {
        $this->assertResponse($this->userRolesQuery, $this->expectedFailedUserRoles(), static::USER_RYAN, 'access');
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

        $this->assertResponse($this->userRolesQuery, $expected, static::USER_ADMIN, 'access');
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
                        'category' => 'user',
                        'locations' => [
                            [
                                'line' => 3,
                                'column' => 5,
                            ],
                        ],
                        'path' => ['user', 'forbidden'],
                    ],
                ],
            ],
        ];

        $query = <<<'EOF'
query MyQuery {
  user {
    forbidden
  }
}
EOF;

        $this->assertResponse($query, $expected, static::USER_ADMIN, 'access');
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

        $this->assertResponse($this->userFriendsQuery, $expected, static::USER_ADMIN, 'access');
    }

    public function testUserAccessToUserFriendsAsArray()
    {
        $expected = [
            'data' => [
                'user' => [
                    'friendsAsArray' => [1, null, 3],
                ],
            ],
        ];

        $this->assertResponse('query { user { friendsAsArray } }', $expected, static::USER_ADMIN, 'access');
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

        $this->assertResponse(\sprintf($this->simpleMutationWithThunkQuery, $result), $expected, static::USER_ADMIN, 'access');
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
                        'category' => 'user',
                        'locations' => [
                            [
                                'line' => 3,
                                'column' => 5,
                            ],
                        ],
                        'path' => ['simpleMutationWithThunkFields', 'result'],
                    ],
                ],
            ],
        ];

        $this->assertResponse(\sprintf($this->simpleMutationWithThunkQuery, 321), $expected, static::USER_ADMIN, 'access');
        $this->assertTrue(SimpleMutationWithThunkFieldsMutation::hasMutate(true));
    }

    public function testMutationNotAllowedUser()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Access denied to this field.',
                    'category' => 'user',
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                    'path' => ['simpleMutationWithThunkFields'],
                ],
            ],
            'data' => [
                'simpleMutationWithThunkFields' => null,
            ],
        ];

        $this->assertResponse(\sprintf($this->simpleMutationWithThunkQuery, 123), $expected, static::USER_RYAN, 'access');
        $this->assertFalse(SimpleMutationWithThunkFieldsMutation::hasMutate(true));
    }

    private function expectedFailedUserRoles()
    {
        return [
            'data' => [
                'user' => [
                    'roles' => null,
                ],
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Access denied to this field.',
                        'category' => 'user',
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 16,
                            ],
                        ],
                        'path' => [
                            'user',
                            'roles',
                        ],
                    ],
                ],
            ],
        ];
    }
}
