<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Controller;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Client;

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
    public function testEndpointAction($uri): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);

        $client->request('GET', $uri, ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/graphql;charset=utf8', 'HTTP_Origin' => 'http://example.com']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], \json_decode($result, true), $result);
        $this->assertCORSHeadersExists($client);
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
    public function testEndpointWithEmptyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', []);
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage The request content body must not be empty when using json content type request.
     */
    public function testEndpointWithEmptyJsonBodyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json']);
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage POST body sent invalid JSON
     */
    public function testEndpointWithInvalidBodyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    public function testEndpointActionWithVariables(): void
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

        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], \json_encode(['query' => $query, 'variables' => '{"firstFriends": 2}']));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Variables are invalid JSON
     */
    public function testEndpointActionWithInvalidVariables(): void
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
    public function testMultipleEndpointActionWithUnknownSchemaName(): void
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = <<<'EOF'
query {
  user
}
EOF;

        $client->request('GET', '/graphql/fake', ['query' => $query]);
    }

    public function testEndpointActionWithOperationName(): void
    {
        $client = static::createClient(['test_case' => 'connection']);

        $query = $this->friendsQuery."\n".$this->friendsTotalCountQuery;

        $client->request('POST', '/', ['query' => $query, 'operationName' => 'FriendsQuery'], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], \json_decode($result, true), $result);
    }

    /**
     * @param $uri
     * @dataProvider graphQLBatchEndpointUriProvider
     */
    public function testBatchEndpointAction($uri): void
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

        $client->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json'], \json_encode($data));
        $result = $client->getResponse()->getContent();

        $expected = [
            ['id' => 'friends', 'payload' => ['data' => $this->expectedData]],
            ['id' => 'friendsTotalCount', 'payload' => ['data' => ['user' => ['friends' => ['totalCount' => 4]]]]],
        ];
        $this->assertSame($expected, \json_decode($result, true), $result);
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
    public function testBatchEndpointWithEmptyQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Batching parser only accepts "application/json" or "multipart/form-data" content-type but got "".
     */
    public function testBatchEndpointWrongContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/batch');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage POST body sent invalid JSON
     */
    public function testBatchEndpointWithInvalidJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage 1 is not a valid query
     */
    public function testBatchEndpointWithInvalidQuery(): void
    {
        $client = static::createClient();
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{"test" : {"query": 1}}');
        $client->getResponse()->getContent();
    }

    public function testPreflightedRequestWhenDisabled(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $client->request('OPTIONS', '/', [], [], ['HTTP_Origin' => 'http://example.com']);
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCORSHeadersNotExists($client);
    }

    public function testUnAuthorizedMethod(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $client->request('PUT', '/', [], [], ['HTTP_Origin' => 'http://example.com']);
        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }

    public function testPreflightedRequestWhenEnabled(): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);
        $client->request('OPTIONS', '/batch', [], [], ['HTTP_Origin' => 'http://example.com']);
        $this->assertCORSHeadersExists($client);
    }

    public function testNoCORSHeadersIfOriginHeaderNotExists(): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);

        $client->request('GET', '/', ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/graphql']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], \json_decode($result, true), $result);
        $this->assertCORSHeadersNotExists($client);
    }

    /**
     * @param Client|KernelBrowser $client
     */
    private function assertCORSHeadersNotExists($client): void
    {
        $headers = $client->getResponse()->headers->all();
        $this->assertArrayNotHasKey('access-control-allow-origin', $headers);
        $this->assertArrayNotHasKey('access-control-allow-methods', $headers);
        $this->assertArrayNotHasKey('access-control-allow-credentials', $headers);
        $this->assertArrayNotHasKey('access-control-allow-headers', $headers);
        $this->assertArrayNotHasKey('access-control-max-age', $headers);
    }

    /**
     * @param Client|KernelBrowser $client
     */
    private function assertCORSHeadersExists($client): void
    {
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('http://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('OPTIONS, GET, POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('Content-Type, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame(3600, $response->headers->get('Access-Control-Max-Age'));
    }
}
