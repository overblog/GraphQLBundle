<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Node;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class NodeTest.
 *
 * @group legacy
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/node/__tests__/node.js
 */
class NodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'node']);
    }

    public function testNodeInterfaceAndFields(): void
    {
        $query = <<<QUERY
        {
          node(id: "1") {
            id
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '1',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectIdForPhotos(): void
    {
        $query = <<<QUERY
        {
          node(id: "4") {
            id
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '4',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectWidthForPhotos(): void
    {
        $query = <<<QUERY
        {
          node(id: "4") {
            id
            ... on Photo {
              width
            }
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '4',
                'width' => 400,
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectTypeNameForUsers(): void
    {
        $query = <<<QUERY
        {
          node(id: "1") {
            id
            __typename
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '1',
                '__typename' => 'User',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectTypeNameForPhotos(): void
    {
        $query = <<<QUERY
        {
          node(id: "4") {
            id
            __typename
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '4',
                '__typename' => 'Photo',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testIgnoresPhotoFragmentsOnUser(): void
    {
        $query = <<<QUERY
        {
          node(id: "1") {
            id
            ... on Photo {
              width
            }
          }
        }
        QUERY;

        $expectedData = [
            'node' => [
                'id' => '1',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testReturnsNullForBadIds(): void
    {
        $query = <<<QUERY
        {
          node(id: "5") {
            id
          }
        }
        QUERY;

        $expectedData = [
            'node' => null,
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testHasCorrectNodeInterface(): void
    {
        $query = <<<QUERY
        {
          __type(name: "Node") {
            name
            kind
            fields {
              name
              type {
                kind
                ofType {
                  name
                  kind
                }
              }
            }
          }
        }
        QUERY;

        $expectedData = [
            '__type' => [
                'name' => 'Node',
                'kind' => 'INTERFACE',
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => [
                            'kind' => 'NON_NULL',
                            'ofType' => [
                                'name' => 'ID',
                                'kind' => 'SCALAR',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testHasCorrectNodeRootField(): void
    {
        $query = <<<QUERY
        {
          __schema {
            queryType {
              fields {
                name
                type {
                  name
                  kind
                }
                args {
                  name
                  type {
                    kind
                    ofType {
                      name
                      kind
                    }
                  }
                }
              }
            }
          }
        }
        QUERY;

        $expectedData = [
            '__schema' => [
                'queryType' => [
                    'fields' => [
                        [
                            'name' => 'node',
                            'type' => [
                                'name' => 'Node',
                                'kind' => 'INTERFACE',
                            ],
                            'args' => [
                                [
                                    'name' => 'id',
                                    'type' => [
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'name' => 'ID',
                                            'kind' => 'SCALAR',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }
}
