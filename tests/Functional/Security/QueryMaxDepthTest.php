<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class QueryMaxDepthTest extends TestCase
{
    private string $userFriendsWithoutViolationQuery = <<<'EOF'
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

    private string $userFriendsWithViolationQuery = <<<'EOF'
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

    public function testMaxDepthReachLimitation(): void
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query depth should be 3 but got 6.',
                    'extensions' => ['category' => 'graphql'],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepth');
    }

    public function testMaxDepthReachLimitationEnv(): void
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'Max query depth should be 3 but got 6.',
                    'extensions' => ['category' => 'graphql'],
                ],
            ],
        ];

        $this->assertResponse($this->userFriendsWithViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepthEnv');
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

        $this->assertResponse($this->userFriendsWithoutViolationQuery, $expected, self::ANONYMOUS_USER, 'queryMaxDepth');
    }
}
