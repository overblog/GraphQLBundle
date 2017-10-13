<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class QueryMaxDepthTest extends TestCase
{
    private $userFriendsWithoutViolationQuery = <<<'EOF'
query {
  user {
    friends(first:1) {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOF;

    private $userFriendsWithViolationQuery = <<<'EOF'
query {
  user {
    friends(first: 1) {
      edges {
        node {
          name
          friends {
            edges {
              node {
                name
              }
            }
          }
        }
      }
    }
  }
}
EOF;

    public function testMaxDepthReachLimitation()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query depth should be 3 but got 6.',
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepth');
    }

    public function testMaxDepthReachLimitationEnv()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query depth should be 3 but got 6.',
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepthEnv');
    }

    public function testComplexityUnderLimitation()
    {
        $expected = [
            'data' => [
                'user' => [
                    'friends' => [
                        'edges' => [
                            ['node' => ['name' => 'Nick']],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithoutViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepth');
    }
}
