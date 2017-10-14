<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Mutation;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class MutationTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/mutation/__tests__/mutation.js
 */
class MutationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'mutation']);
    }

    public function testRequiresAnArgument()
    {
        $query = <<<'EOF'
mutation M {
  simpleMutation {
    result
  }
}
EOF;
        $result = $this->executeGraphQLRequest($query);

        $this->assertCount(1, $result['errors']);
        $this->assertEquals(
            'Field "simpleMutation" argument "input" of type "simpleMutationInput!" is required but not provided.',
            $result['errors'][0]['message']
        );
    }

    public function testReturnTheSameClientMutationId()
    {
        $query = <<<'EOF'
mutation M {
  simpleMutation(input: {clientMutationId: "abc"}) {
    result
    clientMutationId
  }
}
EOF;

        $expectedData = [
            'simpleMutation' => [
                'result' => 1,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testSupportsThunksAsInputAndOutputFields()
    {
        $query = <<<'EOF'
mutation M {
  simpleMutationWithThunkFields(input: {inputData: 1234, clientMutationId: "abc"}) {
    result
    clientMutationId
  }
}
EOF;
        $expectedData = [
            'simpleMutationWithThunkFields' => [
                'result' => 1234,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testSupportsPromiseMutations()
    {
        $query = <<<'EOF'
mutation M {
  simplePromiseMutation(input: {clientMutationId: "abc"}) {
    result
    clientMutationId
  }
}
EOF;
        $expectedData = [
            'simplePromiseMutation' => [
                'result' => 1,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testContainsCorrectInput()
    {
        $query = <<<'EOF'
{
  __type(name: "simpleMutationInput") {
    name
    kind
    inputFields {
      name
      type {
        name
        kind
      }
    }
  }
}
EOF;
        $expectedData = [
            '__type' => [
                'name' => 'simpleMutationInput',
                'kind' => 'INPUT_OBJECT',
                'inputFields' => [
                    [
                        'name' => 'clientMutationId',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testContainsCorrectPayload()
    {
        $query = <<<'EOF'
{
  __type(name: "simpleMutationPayload") {
    name
    kind
    fields {
      name
      type {
        name
        kind
      }
    }
  }
}
EOF;

        $expectedData = [
            '__type' => [
                'name' => 'simpleMutationPayload',
                'kind' => 'OBJECT',
                'fields' => [
                    [
                        'name' => 'result',
                        'type' => [
                            'name' => 'Int',
                            'kind' => 'SCALAR',
                        ],
                    ],
                    [
                        'name' => 'clientMutationId',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testContainsCorrectField()
    {
        $query = <<<'EOF'
{
  __schema {
    mutationType {
      fields {
        name
        args {
          name
          type {
            name
            kind
            ofType {
              name
              kind
            }
          }
        }
        type {
          name
          kind
        }
      }
    }
  }
}
EOF;

        $expectedData = [
            '__schema' => [
                'mutationType' => [
                    'fields' => [
                        [
                            'name' => 'simpleMutation',
                            'args' => [
                                [
                                    'name' => 'input',
                                    'type' => [
                                        'name' => null,
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'name' => 'simpleMutationInput',
                                            'kind' => 'INPUT_OBJECT',
                                        ],
                                    ],
                                ],
                            ],
                            'type' => [
                                'name' => 'simpleMutationPayload',
                                'kind' => 'OBJECT',
                            ],
                        ],
                        [
                            'name' => 'simpleMutationWithThunkFields',
                            'args' => [
                                [
                                    'name' => 'input',
                                    'type' => [
                                        'name' => null,
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'name' => 'simpleMutationWithThunkFieldsInput',
                                            'kind' => 'INPUT_OBJECT',
                                        ],
                                    ],
                                ],
                            ],
                            'type' => [
                                'name' => 'simpleMutationWithThunkFieldsPayload',
                                'kind' => 'OBJECT',
                            ],
                        ],
                        [
                            'name' => 'simplePromiseMutation',
                            'args' => [
                                [
                                    'name' => 'input',
                                    'type' => [
                                        'name' => null,
                                        'kind' => 'NON_NULL',
                                        'ofType' => [
                                            'name' => 'simplePromiseMutationInput',
                                            'kind' => 'INPUT_OBJECT',
                                        ],
                                    ],
                                ],
                            ],
                            'type' => [
                                'name' => 'simplePromiseMutationPayload',
                                'kind' => 'OBJECT',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }
}
