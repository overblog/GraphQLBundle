<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Controller;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class GraphControllerTest extends TestCase
{
    private $expectedData = [
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

    public function testEndpointAction()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<EOF
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
        $client->request('GET', '/', ['query' => $query], [], ['CONTENT_TYPE' => 'application/graphql']);
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Must provide query parameter
     */
    public function testEndpointWithEmptyQuery()
    {
        $client = static::createClient();
        $client->request('GET', '/', []);
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage POST body sent invalid JSON [Syntax error]
     */
    public function testEndpointWithInvalidBodyQuery()
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    public function testEndpointActionWithVariables()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<EOF
query FriendsQuery(\$firstFriends: Int) {
  user {
    friends(first: \$firstFriends) {
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

        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['query' => $query, 'variables' => '{"firstFriends": 2}']));
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Variables are invalid JSON
     */
    public function testEndpointActionWithInvalidVariables()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<EOF
query {
  user
}
EOF;

        $client->request('GET', '/', ['query' => $query, 'variables' => '"firstFriends": 2}']);
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    public function testEndpointActionWithOperationName()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<EOF
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

query FriendsQuery2 {
  user {
    friends {
      totalCount
    }
  }
}
EOF;

        $client->request('POST', '/', ['query' => $query, 'operationName' => 'FriendsQuery'], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }
}
