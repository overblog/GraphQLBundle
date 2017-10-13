<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Connection;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class ConnectionTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/connection.js
 */
class ConnectionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'connection']);
    }

    public function testIncludesConnectionAndEdgeFields()
    {
        $query = <<<'EOF'
query FriendsQuery {
  user {
    friends(first: 2) {
      totalCount
      edges {
        friendshipTime
        node {
          name
        }
      }
    }
  }
}
EOF;

        $expectedData = [
            'user' => [
                'friends' => [
                   'totalCount' => 4,
                    'edges' => [
                        [
                            'friendshipTime' => 'Yesterday',
                            'node' => [
                                'name' => 'Nick',
                            ],
                        ],
                        [
                            'friendshipTime' => 'Yesterday',
                            'node' => [
                                'name' => 'Lee',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testWorksWithForwardConnectionArgs()
    {
        $query = <<<'EOF'
query FriendsQuery {
  user {
    friendsForward(first: 2) {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOF;

        $expectedData = [
            'user' => [
                'friendsForward' => [
                    'edges' => [
                        [
                            'node' => [
                                'name' => 'Nick',
                            ],
                        ],
                        [
                            'node' => [
                                'name' => 'Lee',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testWorksWithBackwardConnectionArgs()
    {
        $query = <<<'EOF'
      query FriendsQuery {
        user {
          friendsBackward(last: 2) {
            edges {
              node {
                name
              }
            }
          }
        }
      }
EOF;

        $expectedData = [
            'user' => [
                'friendsBackward' => [
                    'edges' => [
                        [
                            'node' => [
                                'name' => 'Joe',
                            ],
                        ],
                        [
                            'node' => [
                                'name' => 'Tim',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }
}
