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
        $query = <<<'EOF'
{
  node(id: "1") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => [
                'id' => '1',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectIdForPhotos(): void
    {
        $query = <<<'EOF'
{
  node(id: "4") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => [
                'id' => '4',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testGetsTheCorrectWidthForPhotos(): void
    {
        $query = <<<'EOF'
{
  node(id: "4") {
    id
    ... on Photo {
      width
    }
  }
}
EOF;

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
        $query = <<<'EOF'
{
  node(id: "1") {
    id
    __typename
  }
}
EOF;

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
        $query = <<<'EOF'
{
  node(id: "4") {
    id
    __typename
  }
}
EOF;

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
        $query = <<<'EOF'
{
  node(id: "1") {
    id
    ... on Photo {
      width
    }
  }
}
EOF;

        $expectedData = [
            'node' => [
                'id' => '1',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testReturnsNullForBadIds(): void
    {
        $query = <<<'EOF'
{
  node(id: "5") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => null,
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testHasCorrectNodeInterface(): void
    {
        $query = <<<'EOF'
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
EOF;

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
        $query = <<<'EOF'
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
EOF;

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
