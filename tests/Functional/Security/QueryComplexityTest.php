<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class QueryComplexityTest extends TestCase
{
    private string $userFriendsWithoutLimitQuery = <<<'EOF'
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

    private string $userFriendsWithLimitQuery = <<<'EOF'
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

    public function testComplexityReachLimitation(): void
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query complexity should be 10 but got 54.',
                    'extensions' => ['category' => 'graphql'],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithoutLimitQuery, $expected, self::ANONYMOUS_USER, 'queryComplexity');
    }

    public function testComplexityReachLimitationEnv(): void
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query complexity should be 10 but got 54.',
                    'extensions' => ['category' => 'graphql'],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithoutLimitQuery, $expected, self::ANONYMOUS_USER, 'queryComplexityEnv');
    }

    public function testComplexityUnderLimitation(): void
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
