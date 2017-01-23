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

class QueryComplexityTest extends TestCase
{
    private $userFriendsWithoutLimitQuery = <<<'EOF'
query MyQuery {
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
query MyQuery {
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

        $this->assertResponse($this->userFriendsWithoutLimitQuery, $expected);
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

        $this->assertResponse($this->userFriendsWithLimitQuery, $expected);
    }

    private static function assertResponse($query, array $expected)
    {
        $client = static::createClient(['test_case' => 'queryComplexity']);
        $client->request('GET', '/', ['query' => $query]);

        $result = $client->getResponse()->getContent();

        static::assertEquals($expected, json_decode($result, true), $result);

        return $client;
    }
}
