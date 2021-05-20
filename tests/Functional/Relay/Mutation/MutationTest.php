<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Relay\Mutation;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

/**
 * Class MutationTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/mutation/__tests__/mutation.js
 */
class MutationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'mutation']);
    }

    public function testRequiresAnArgument(): void
    {
        $query = <<<MUTATION
            mutation M {
              simpleMutation {
                result
              }
            }
            MUTATION;

        $result = $this->executeGraphQLRequest($query);

        $this->assertCount(1, $result['errors']);
        $this->assertSame(
            'Field "simpleMutation" argument "input" of type "simpleMutationInput!" is required but not provided.',
            $result['errors'][0]['message']
        );
    }

    public function testReturnTheSameClientMutationId(): void
    {
        $query = <<<MUTATION
            mutation M {
              simpleMutation(input: {clientMutationId: "abc"}) {
                result
                clientMutationId
              }
            }
            MUTATION;

        $expectedData = [
            'simpleMutation' => [
                'result' => 1,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testSupportsThunksAsInputAndOutputFields(): void
    {
        $query = <<<MUTATION
            mutation M {
              simpleMutationWithThunkFields(input: {inputData: 1234, clientMutationId: "abc"}) {
                result
                clientMutationId
              }
            }
            MUTATION;

        $expectedData = [
            'simpleMutationWithThunkFields' => [
                'result' => 1234,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testSupportsPromiseMutations(): void
    {
        $query = <<<MUTATION
            mutation M {
              simplePromiseMutation(input: {clientMutationId: "abc"}) {
                result
                clientMutationId
              }
            }
            MUTATION;

        $expectedData = [
            'simplePromiseMutation' => [
                'result' => 1,
                'clientMutationId' => 'abc',
            ],
        ];

        $this->assertGraphQL($query, $expectedData);
    }

    public function testContainsCorrectInput(): void
    {
        $query = <<<MUTATION
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
            MUTATION;

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

    public function testContainsCorrectPayload(): void
    {
        $query = <<<MUTATION
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
            MUTATION;

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

    public function testContainsCorrectField(): void
    {
        $query = <<<MUTATION
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
            MUTATION;

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
