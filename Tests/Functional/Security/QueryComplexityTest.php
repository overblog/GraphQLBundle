<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class QueryComplexityTest extends TestCase
{
    private $userFriendsWithoutLimitQuery = <<<'EOF'
query {
  user {
    friends {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOF;

    private $userFriendsWithLimitQuery = <<<'EOF'
query {
  user {
    friends(first: 1) {
      edges {
        node {
          name
        }
      }
    }
  }
}
EOF;

    public function testComplexityReachLimitation()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query complexity should be 10 but got 54.',
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithoutLimitQuery, $expected, self::ANONYMOUS_USER, 'queryComplexity');
    }

    public function testComplexityReachLimitationEnv()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query complexity should be 10 but got 54.',
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithoutLimitQuery, $expected, self::ANONYMOUS_USER, 'queryComplexityEnv');
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

        $this->assertResponse($this->userFriendsWithLimitQuery, $expected, self::ANONYMOUS_USER, 'queryComplexity');
    }
}
