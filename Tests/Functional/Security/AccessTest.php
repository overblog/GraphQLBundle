<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Composer\Autoload\ClassLoader;
use Overblog\GraphQLBundle\Tests\Functional\App\Mutation\SimpleMutationWithThunkFieldsMutation;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\HttpKernel\Kernel;

class AccessTest extends TestCase
{
    /** @var ClassLoader */
    private $loader;

    private $userNameQuery = 'query { user { name } }';

    private $userRolesQuery = 'query { user { roles } }';

    private $userIsEnabledQuery = 'query { user { isEnabled } }';

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
        /** @var ClassLoader $loader */
        $loader = new ClassLoader();
        $loader->addPsr4(
            'Overblog\\GraphQLBundle\\Access\\__DEFINITIONS__\\',
            '/tmp/OverblogGraphQLBundle/'.Kernel::VERSION.'/access/cache/testaccess/overblog/graphql-bundle/__definitions__'
        );
        $loader->register();
        $this->loader = $loader;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Type class "Overblog\\GraphQLBundle\\Access\\__DEFINITIONS__\\PageInfoType" not found. If you are using your own classLoader verify the path and the namespace please.
     */
    public function testCustomClassLoaderNotRegister()
    {
        $this->loader->unregister();
        $this->assertResponse($this->userNameQuery, [], static::ANONYMOUS_USER, 'access');
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
                        'locations' => [['line' => 1, 'column' => 16]],
                        'path' => ['user', 'name'],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->userNameQuery, $expected, static::ANONYMOUS_USER, 'access');
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

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, $result), $expected, static::USER_ADMIN, 'access');
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
                        'path' => ['simpleMutationWithThunkFields', 'result'],
                    ],
                ],
            ],
        ];

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, 321), $expected, static::USER_ADMIN, 'access');
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
                    'path' => ['simpleMutationWithThunkFields'],
                ],
            ],
        ];

        $this->assertResponse(sprintf($this->simpleMutationWithThunkQuery, 123), $expected, static::USER_RYAN, 'access');
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
}
