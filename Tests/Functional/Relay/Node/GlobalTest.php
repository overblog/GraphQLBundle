<?php

namespace Overblog\GraphBundle\Tests\Functional\Relay\Node;


use Overblog\GraphBundle\Tests\Functional\TestCase;

/**
 * Class GlobalTest
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/node/__tests__/global.js
 */
class GlobalTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::$kernel = static::createKernel(['test_case' => 'global']);
        static::$kernel->boot();
    }

    public function testGlobalIdFields()
    {
        $query = <<<EOF
{
  allObjects {
    id
  }
}
EOF;

        $expectedData = [
            'allObjects' => [
                [
                    'id' => 'VXNlcjox'
                ],
                [
                    'id' => 'VXNlcjoy'
                ],
                [
                    'id' => 'UGhvdG86MQ=='
                ],
                [
                    'id' => 'UGhvdG86Mg=='
                ],
                [
                    'id' => 'UG9zdDox'
                ],
                [
                    'id' => 'UG9zdDoy'
                ],
            ],
        ];

        $this->assertGraphQl($query, $expectedData);
    }

    public function testReFetchesTheIds()
    {
        $query = <<<EOF
{
      user: node(id: "VXNlcjox") {
        id
        ... on User {
          name
        }
      },
      photo: node(id: "UGhvdG86MQ==") {
        id
        ... on Photo {
          width
        }
      },
      post: node(id: "UG9zdDox") {
        id
        ... on Post {
          text
        }
      }
    }
EOF;
        $expectedData = [
            'user' => [
                'id' => 'VXNlcjox',
                'name' => 'John Doe'
            ],
            'photo' => [
                'id' => 'UGhvdG86MQ==',
                'width' => 300
            ],
            'post' => [
                'id' => 'UG9zdDox',
                'text' => 'lorem'
            ]
        ];

        $this->assertGraphQl($query, $expectedData);
    }
}
