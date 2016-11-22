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
    private $friendsQuery = <<<'EOF'
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

    private $friendsTotalCountQuery = <<<'EOF'
query FriendsTotalCountQuery {
  user {
    friends {
      totalCount
    }
  }
}
EOF;

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

    /**
     * @param $uri
     * @dataProvider graphQLEndpointUriProvider
     */
    public function testEndpointAction($uri)
    {
        $client = static::createClient(['test_case' => 'connection']);

        $client->request('GET', $uri, ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/graphql']);
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    public function graphQLEndpointUriProvider()
    {
        return [
            ['/'],
            ['/graphql/default'],
        ];
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
     * @expectedExceptionMessage The request content body must not be empty when using json content type request.
     */
    public function testEndpointWithEmptyJsonBodyQuery()
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json']);
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage POST body sent invalid JSON
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

        $query = <<<'EOF'
query FriendsQuery($firstFriends: Int) {
  user {
    friends(first: $firstFriends) {
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
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Variables are invalid JSON
     */
    public function testEndpointActionWithInvalidVariables()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<'EOF'
query {
  user
}
EOF;

        $client->request('GET', '/', ['query' => $query, 'variables' => '"firstFriends": 2}']);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Could not found "fake" schema.
     */
    public function testMultipleEndpointActionWithUnknownSchemaName()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<'EOF'
query {
  user
}
EOF;

        $client->request('GET', '/graphql/fake', ['query' => $query]);
    }

    public function testEndpointActionWithOperationName()
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = $this->friendsQuery."\n".$this->friendsTotalCountQuery;

        $client->request('POST', '/', ['query' => $query, 'operationName' => 'FriendsQuery'], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $result = $client->getResponse()->getContent();
        $this->assertEquals(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    /**
     * @param $uri
     * @dataProvider graphQLBatchEndpointUriProvider
     */
    public function testBatchEndpointAction($uri)
    {
        $client = static::createClient(['test_case' => 'connection']);

        $data = [
            [
                'id' => 'friends',
                'query' => $this->friendsQuery,
            ],
            [
                'id' => 'friendsTotalCount',
                'query' => $this->friendsTotalCountQuery,
            ],
        ];

        $client->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $result = $client->getResponse()->getContent();

        $expected  = [
            ['id' => 'friends', 'payload' => ['data' => $this->expectedData]],
            ['id' => 'friendsTotalCount', 'payload' => ['data' => ['user' => ['friends' => ['totalCount' => 4]]]]],
        ];
        $this->assertEquals($expected, json_decode($result, true), $result);
    }

    public function graphQLBatchEndpointUriProvider()
    {
        return [
            ['/batch'],
            ['/graphql/default/batch'],
        ];
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Must provide at least one valid query.
     */
    public function testBatchEndpointWithEmptyQuery()
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Only request with content type "application/json" is accepted.
     */
    public function testBatchEndpointWrongContentType()
    {
        $client = static::createClient();
        $client->request('GET', '/batch');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage POST body sent invalid JSON
     */
    public function testBatchEndpointWithInvalidJson()
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage 1 is not a valid query
     */
    public function testBatchEndpointWithInvalidQuery()
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{"test" : {"query": 1}}');
        $client->getResponse()->getContent();
    }
}
