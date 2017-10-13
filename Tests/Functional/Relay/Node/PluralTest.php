<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Node;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class PluralTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/node/__tests__/plural.js
 */
class PluralTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'plural']);
    }

    public function testNodeInterfaceAndFields()
    {
        $query = <<<'EOF'
{
      usernames(usernames:["dschafer", "leebyron", "schrockn"]) {
        username
        url
      }
    }
EOF;

        $expectedData = [
            'usernames' => [
                [
                    'username' => 'dschafer',
                    'url' => 'www.facebook.com/dschafer?lang=en',
                ],
                [
                    'username' => 'leebyron',
                    'url' => 'www.facebook.com/leebyron?lang=en',
                ],
                [
                    'username' => 'schrockn',
                    'url' => 'www.facebook.com/schrockn?lang=en',
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData, null, ['lang' => 'en']);
    }

    public function testCorrectlyIntrospects()
    {
        $query = <<<'EOF'
{
      __schema {
        queryType {
          fields {
            name
            args {
              name
              type {
                kind
                ofType {
                  kind
                  ofType {
                    kind
                    ofType {
                      name
                      kind
                    }
                  }
                }
              }
            }
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
EOF;

        $expectedData = [
            '__schema' => [
                'queryType' => [
                    'fields' => [
                        [
                            'name' => 'usernames',
                            'args' => [
                                [
                                    'name' => 'usernames',
                                    'type' => [
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'kind' => 'LIST',
                                            'ofType' => [
                                                'kind' => 'NON_NULL',
                                                'ofType' => [
                                                    'name' => 'String',
                                                    'kind' => 'SCALAR',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'type' => [
                                'kind' => 'LIST',
                                'ofType' => [
                                    'name' => 'User',
                                    'kind' => 'OBJECT',
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
