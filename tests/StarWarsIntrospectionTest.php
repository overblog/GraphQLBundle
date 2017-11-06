<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

use GraphQL\GraphQL;

class StarWarsIntrospectionTest extends AbstractStarWarsTest
{
    // Star Wars Introspection Tests
    // Basic Introspection
    // it('Allows querying the schema for types')
    public function testAllowsQueryingTheSchemaForTypes()
    {
        $query = '
        query IntrospectionTypeQuery {
          __schema {
            types {
              name
            }
          }
        }
        ';
        $expected = [
            '__schema' => [
                'types' => [
                    ['name' => 'ID'],
                    ['name' => 'String'],
                    ['name' => 'Float'],
                    ['name' => 'Int'],
                    ['name' => 'Boolean'],
                    ['name' => '__Schema'],
                    ['name' => '__Type'],
                    ['name' => '__TypeKind'],
                    ['name' => '__Field'],
                    ['name' => '__InputValue'],
                    ['name' => '__EnumValue'],
                    ['name' => '__Directive'],
                    ['name' => '__DirectiveLocation'],
                    ['name' => 'Query'],
                    ['name' => 'HeroInput'],
                    ['name' => 'Episode'],
                    ['name' => 'Character'],
                    ['name' => 'Human'],
                    ['name' => 'Droid'],
                    ['name' => 'DateTime'],
                ]
            ]
        ];

        $actual = GraphQL::executeQuery($this->schema, $query)->toArray();
        $this->sortSchemaEntry($actual, 'types', 'name');
        $this->sortSchemaEntry($expected, 'types', 'name');
        $expected = ['data' => $expected];
        $this->assertEquals($expected, $actual, json_encode($actual));
    }

    // it('Allows querying the schema for query type')
    public function testAllowsQueryingTheSchemaForQueryType()
    {
        $query = '
        query IntrospectionQueryTypeQuery {
          __schema {
            queryType {
              name
            }
          }
        }
        ';
        $expected = [
            '__schema' => [
                'queryType' => [
                    'name' => 'Query'
                ],
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for a specific type')
    public function testAllowsQueryingTheSchemaForASpecificType()
    {
        $query = '
        query IntrospectionDroidTypeQuery {
          __type(name: "Droid") {
            name
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Droid'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for an object kind')
    public function testAllowsQueryingForAnObjectKind()
    {
        $query = '
        query IntrospectionDroidKindQuery {
          __type(name: "Droid") {
            name
            kind
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Droid',
                'kind' => 'OBJECT'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for an interface kind')
    public function testAllowsQueryingForInterfaceKind()
    {
        $query = '
        query IntrospectionCharacterKindQuery {
          __type(name: "Character") {
            name
            kind
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Character',
                'kind' => 'INTERFACE'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for object fields')
    public function testAllowsQueryingForObjectFields()
    {
        $query = '
        query IntrospectionDroidFieldsQuery {
          __type(name: "Droid") {
            name
            fields {
              name
              type {
                name
                kind
              }
            }
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Droid',
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => [
                            'name' => null,
                            'kind' => 'NON_NULL'
                        ]
                    ],
                    [
                        'name' => 'name',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR'
                        ]
                    ],
                    [
                        'name' => 'friends',
                        'type' => [
                            'name' => null,
                            'kind' => 'LIST'
                        ]
                    ],
                    [
                        'name' => 'appearsIn',
                        'type' => [
                            'name' => null,
                            'kind' => 'LIST'
                        ]
                    ],
                    [
                        'name' => 'primaryFunction',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR'
                        ]
                    ]
                ]
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for nested object fields')
    public function testAllowsQueryingTheSchemaForNestedObjectFields()
    {
        $query = '
        query IntrospectionDroidNestedFieldsQuery {
          __type(name: "Droid") {
            name
            fields {
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
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Droid',
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => [
                            'name' => null,
                            'kind' => 'NON_NULL',
                            'ofType' => [
                                'name' => 'String',
                                'kind' => 'SCALAR'
                            ]
                        ]
                    ],
                    [
                        'name' => 'name',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR',
                            'ofType' => null
                        ]
                    ],
                    [
                        'name' => 'friends',
                        'type' => [
                            'name' => null,
                            'kind' => 'LIST',
                            'ofType' => [
                                'name' => 'Character',
                                'kind' => 'INTERFACE'
                            ]
                        ]
                    ],
                    [
                        'name' => 'appearsIn',
                        'type' => [
                            'name' => null,
                            'kind' => 'LIST',
                            'ofType' => [
                                'name' => 'Episode',
                                'kind' => 'ENUM'
                            ]
                        ]
                    ],
                    [
                        'name' => 'primaryFunction',
                        'type' => [
                            'name' => 'String',
                            'kind' => 'SCALAR',
                            'ofType' => null
                        ]
                    ]
                ]
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testAllowsQueryingTheSchemaForFieldArgs()
    {
        $query = '
        query IntrospectionQueryTypeQuery {
          __schema {
            queryType {
              fields {
                name
                args {
                  name
                  description
                  type {
                    name
                    kind
                    ofType {
                      name
                      kind
                    }
                  }
                  defaultValue
                }
              }
            }
          }
        }
        ';
        $expected = [
            '__schema' => [
                'queryType' => [
                    'fields' => [
                        [
                            'name' => 'hero',
                            'args' => [
                                [
                                    'defaultValue' =>  null,
                                    'description' => "If omitted, returns the hero of the whole saga.\nIf provided, returns the hero of that particular episode.\n",
                                    'name' => 'episode',
                                    'type' => [
                                        'kind' => 'INPUT_OBJECT',
                                        'name' => 'HeroInput',
                                        'ofType' => null,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'human',
                            'args' => [
                                [
                                    'name' => 'id',
                                    'description' => 'id of the human',
                                    'type' => [
                                        'kind' => 'NON_NULL',
                                        'name' => null,
                                        'ofType' => [
                                            'kind' => 'SCALAR',
                                            'name' => 'String',
                                        ],
                                    ],
                                    'defaultValue' => null,
                                ],
                            ],
                        ],
                        [
                            'name' => 'droid',
                            'args' => [
                                [
                                    'name' => 'id',
                                    'description' => 'id of the droid',
                                    'type' => [
                                        'kind' => 'NON_NULL',
                                        'name' => null,
                                        'ofType' =>
                                            [
                                                'kind' => 'SCALAR',
                                                'name' => 'String',
                                            ],
                                    ],
                                    'defaultValue' => null,
                                ],
                            ],
                        ],
                        [
                            'name' => 'dateTime',
                            'args' => [
                                [
                                    'name' => 'dateTime',
                                    'description' => null,
                                    'type' => [
                                        'name' => 'DateTime',
                                        'kind' => 'SCALAR',
                                        'ofType' => null,
                                    ],
                                    'defaultValue' => null,
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertValidQuery($query, $expected);
    }

    // it('Allows querying the schema for documentation')
    public function testAllowsQueryingTheSchemaForDocumentation()
    {
        $query = '
        query IntrospectionDroidDescriptionQuery {
          __type(name: "Droid") {
            name
            description
          }
        }
        ';
        $expected = [
            '__type' => [
                'name' => 'Droid',
                'description' => 'A mechanical creature in the Star Wars universe.'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }
}
