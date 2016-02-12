<?php

namespace Overblog\GraphBundle\Tests\Functional\Relay\Node;


use Overblog\GraphBundle\Tests\Functional\TestCase;

/**
 * Class NodeTest
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/node/__tests__/node.js
 */
class NodeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::$kernel = static::createKernel(['test_case' => 'node']);
        static::$kernel->boot();
    }

    public function testNodeInterfaceAndFields()
    {
        $query = <<<EOF
{
  node(id: "1") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => [
                'id' => '1'
            ],
        ];

        $this->assertGraphQl($query, $expectedData);
    }

    public function testGetsTheCorrectIdForPhotos()
    {
        $query = <<<EOF
{
  node(id: "4") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => [
                'id' => '4'
            ],
        ];

        $this->assertGraphQl($query, $expectedData);
    }

    public function testGetsTheCorrectWidthForPhotos()
    {
        $query = <<<EOF
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

        $this->assertGraphQl($query, $expectedData);
    }

    public function testGetsTheCorrectTypeNameForUsers()
    {
        $query = <<<EOF
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

        $this->assertGraphQl($query, $expectedData);
    }

    public function testGetsTheCorrectTypeNameForPhotos()
    {
        $query = <<<EOF
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

        $this->assertGraphQl($query, $expectedData);
    }

    public function testIgnoresPhotoFragmentsOnUser()
    {
        $query = <<<EOF
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

        $this->assertGraphQl($query, $expectedData);
    }

    public function testReturnsNullForBadIds()
    {
        $query = <<<EOF
{
  node(id: "5") {
    id
  }
}
EOF;

        $expectedData = [
            'node' => null,
        ];

        $this->assertGraphQl($query, $expectedData);
    }

    public function testHasCorrectNodeInterface()
    {
        $query = <<<EOF
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
                                'kind' => 'SCALAR'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertGraphQl($query, $expectedData);
    }

    public function testHasCorrectNodeRootField()
    {
        $query = <<<EOF
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
                                'kind' => 'INTERFACE'
                            ],
                            'args' => [
                                [
                                    'name' => 'id',
                                    'type' => [
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'name' => 'ID',
                                            'kind' => 'SCALAR'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertGraphQl($query, $expectedData);
    }
}
