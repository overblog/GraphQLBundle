<?php

declare(strict_types=1);

return [
    'Query' => [
        'type' => 'object',
        'config' => [
            'description' => 'Root Query',
            'fields' => [
                'hero' => [
                    'type' => 'Character',
                    'description' => null,
                    'args' => [
                        'episodes' => [
                            'type' => '[Episode!]!',
                            'description' => 'Episode list to use to filter',
                            'defaultValue' => ['NEWHOPE', 'EMPIRE'],
                        ],
                    ],
                ],
                'droid' => [
                    'type' => 'Droid',
                    'description' => 'search for a droid',
                    'args' => [
                        'id' => [
                            'type' => 'ID!',
                            'description' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],
    'Starship' => [
        'type' => 'object',
        'config' => [
            'description' => null,
            'fields' => [
                'id' => ['type' => 'ID!', 'description' => null],
                'name' => ['type' => 'String!', 'description' => null],
                'length' => [
                    'type' => 'Float',
                    'description' => null,
                    'args' => [
                        'unit' => [
                            'type' => 'LengthUnit',
                            'description' => null,
                            'defaultValue' => 'METER',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'Episode' => [
        'type' => 'enum',
        'config' => [
            'description' => null,
            'values' => [
                'NEWHOPE' => [
                    'description' => null,
                    'value' => 'NEWHOPE',
                ],
                'EMPIRE' => [
                    'description' => 'Star Wars: Episode V – The Empire Strikes Back',
                    'value' => 'EMPIRE',
                ],
                'JEDI' => [
                    'description' => null,
                    'value' => 'JEDI',
                    'deprecationReason' => 'No longer supported',
                ],
            ],
        ],
    ],
    'Character' => [
        'type' => 'interface',
        'config' => [
            'description' => null,
            'fields' => [
                'id' => ['type' => 'ID!', 'description' => null],
                'name' => ['type' => 'String!', 'description' => null],
                'friends' => ['type' => '[Character]', 'description' => null],
                'appearsIn' => ['type' => '[Episode]!', 'description' => null],
                'deprecatedField' => [
                    'type' => 'String!',
                    'description' => null,
                    'deprecationReason' => 'This field was deprecated!',
                ],
            ],
        ],
    ],
    'Human' => [
        'type' => 'object',
        'config' => [
            'description' => null,
            'fields' => [
                'id' => ['type' => 'ID!', 'description' => null],
                'name' => ['type' => 'String!', 'description' => null],
                'friends' => ['type' => '[Character]', 'description' => null],
                'appearsIn' => ['type' => '[Episode]!', 'description' => null],
                'starships' => ['type' => '[Starship]', 'description' => null],
                'totalCredits' => ['type' => 'Int', 'description' => null],
            ],
            'interfaces' => ['Character'],
        ],
    ],
    'Droid' => [
        'type' => 'object',
        'config' => [
            'description' => null,
            'fields' => [
                'id' => ['type' => 'ID!', 'description' => null],
                'name' => ['type' => 'String!', 'description' => null],
                'friends' => ['type' => '[Character]', 'description' => null],
                'appearsIn' => ['type' => '[Episode]!', 'description' => null],
                'primaryFunction' => ['type' => 'String', 'description' => null],
            ],
            'interfaces' => ['Character'],
        ],
    ],
    'SearchResult' => [
        'type' => 'union',
        'config' => [
            'description' => null,
            'types' => ['Human', 'Droid', 'Starship'],
        ],
    ],
    'ReviewInput' => [
        'type' => 'input-object',
        'config' => [
            'description' => null,
            'fields' => [
                'stars' => ['type' => 'Int!', 'description' => null, 'defaultValue' => 5],
                'rate' => ['type' => 'Float!', 'description' => null, 'defaultValue' => 1.58],
                'commentary' => ['type' => 'String', 'description' => null, 'defaultValue' => null],
            ],
        ],
    ],
    'Year' => [
        'type' => 'custom-scalar',
        'config' => [
            'description' => null,
            'serialize' => [\Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\CustomScalarNode::class, 'mustOverrideConfig'],
            'parseValue' => [\Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\CustomScalarNode::class, 'mustOverrideConfig'],
            'parseLiteral' => [\Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter\CustomScalarNode::class, 'mustOverrideConfig'],
        ],
    ],
];
