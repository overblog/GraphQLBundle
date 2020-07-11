<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Controller;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function json_decode;
use function json_encode;

class GraphControllerTest extends TestCase
{
    private string $friendsQuery = <<<'EOF'
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

    private string $friendsTotalCountQuery = <<<'EOF'
    query FriendsTotalCountQuery {
      user {
        friends {
          totalCount
        }
      }
    }
    EOF;

    private array $expectedData = [
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
     * @dataProvider graphQLEndpointUriProvider
     */
    public function testEndpointAction(string $uri): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);
        $this->disableCatchExceptions($client);

        $client->request('GET', $uri, ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/graphql;charset=utf8', 'HTTP_Origin' => 'http://example.com']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], json_decode($result, true), $result);
        $this->assertCORSHeadersExists($client);
    }

    public function graphQLEndpointUriProvider(): array
    {
        return [
            ['/'],
            ['/graphql/default'],
        ];
    }

    public function testEndpointWithEmptyQuery(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Must provide query parameter');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/', []);
        $client->getResponse()->getContent();
    }

    public function testEndpointWithEmptyPostJsonBodyQuery(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The request content body must not be empty when using json content type request.');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('POST', '/', [], [], ['CONTENT_TYPE' => 'application/json']);
    }

    public function testEndpointWithJsonContentTypeAndGetQuery(): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);
        $this->disableCatchExceptions($client);
        $client->request('GET', '/', ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/json']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    public function testEndpointWithInvalidBodyQuery(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('POST body sent invalid JSON');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    public function testEndpointActionWithVariables(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);

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

        $content = json_encode(['query' => $query, 'variables' => '{"firstFriends": 2}']) ?: null;
        $client->request('GET', '/', [], [], ['CONTENT_TYPE' => 'application/json'], $content);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEndpointActionWithInvalidVariables(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Variables are invalid JSON');
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);

        $query = <<<'EOF'
query {
  user
}
EOF;

        $client->request('GET', '/', ['query' => $query, 'variables' => '"firstFriends": 2}']);
    }

    public function testMultipleEndpointActionWithUnknownSchemaName(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Could not found "fake" schema.');
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);

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
        $this->disableCatchExceptions($client);

        $query = $this->friendsQuery."\n".$this->friendsTotalCountQuery;

        $client->request('POST', '/', ['query' => $query, 'operationName' => 'FriendsQuery'], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], json_decode($result, true), $result);
    }

    /**
     * @dataProvider graphQLBatchEndpointUriProvider
     */
    public function testBatchEndpointAction(string $uri): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);

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

        $content = json_encode($data) ?: null;
        $client->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json'], $content);
        $result = $client->getResponse()->getContent();

        $expected = [
            ['id' => 'friends', 'payload' => ['data' => $this->expectedData]],
            ['id' => 'friendsTotalCount', 'payload' => ['data' => ['user' => ['friends' => ['totalCount' => 4]]]]],
        ];
        $this->assertSame($expected, json_decode($result, true), $result);
    }

    public function graphQLBatchEndpointUriProvider(): array
    {
        return [
            ['/batch'],
            ['/graphql/default/batch'],
        ];
    }

    public function testBatchEndpointWithEmptyQuery(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Must provide at least one valid query.');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $client->getResponse()->getContent();
    }

    public function testBatchEndpointWrongContentType(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Batching parser only accepts "application/json" or "multipart/form-data" content-type but got "".');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/batch');
        $client->getResponse()->getContent();
    }

    public function testBatchEndpointWithInvalidJson(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('POST body sent invalid JSON');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{');
        $client->getResponse()->getContent();
    }

    public function testBatchEndpointWithInvalidQuery(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('1 is not a valid query');
        $client = static::createClient();
        $this->disableCatchExceptions($client);
        $client->request('GET', '/batch', [], [], ['CONTENT_TYPE' => 'application/json'], '{"test" : {"query": 1}}');
        $client->getResponse()->getContent();
    }

    public function testPreflightedRequestWhenDisabled(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);
        $client->request('OPTIONS', '/', [], [], ['HTTP_Origin' => 'http://example.com']);
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCORSHeadersNotExists($client);
    }

    public function testUnAuthorizedMethod(): void
    {
        $client = static::createClient(['test_case' => 'connection']);
        $this->disableCatchExceptions($client);
        $client->request('PUT', '/', [], [], ['HTTP_Origin' => 'http://example.com']);
        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }

    public function testPreflightedRequestWhenEnabled(): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);
        $this->disableCatchExceptions($client);
        $client->request('OPTIONS', '/batch', [], [], ['HTTP_Origin' => 'http://example.com']);
        $this->assertCORSHeadersExists($client);
    }

    public function testNoCORSHeadersIfOriginHeaderNotExists(): void
    {
        $client = static::createClient(['test_case' => 'connectionWithCORS']);
        $this->disableCatchExceptions($client);
        $client->request('GET', '/', ['query' => $this->friendsQuery], [], ['CONTENT_TYPE' => 'application/graphql']);
        $result = $client->getResponse()->getContent();
        $this->assertSame(['data' => $this->expectedData], json_decode($result, true), $result);
        $this->assertCORSHeadersNotExists($client);
    }

    /**
     * @param KernelBrowser $client
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
     * @param KernelBrowser $client
     */
    private function assertCORSHeadersExists($client): void
    {
        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('http://example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('OPTIONS, GET, POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('Content-Type, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }
}
