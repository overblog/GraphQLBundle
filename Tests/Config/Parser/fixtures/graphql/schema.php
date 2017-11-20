<?php

return [
    'Query' => [
        'type' => 'object',
        'config' => [
            'description' => 'Root Query',
            'fields' => [
                'hero' => [
                    'type' => 'Character',
                    'arguments' => [
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
                    'arguments' => [
                        'id' => [
                            'type' => 'ID!',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'Starship' => [
        'type' => 'object',
        'config' => [
            'fields' => [
                'id' => ['type' => 'ID!'],
                'name' => ['type' => 'String!'],
                'length' => [
                    'type' => 'Float',
                    'arguments' => [
                        'unit' => [
                            'type' => 'LengthUnit',
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
            'values' => [
                ['value' => 'NEWHOPE'],
                ['value' => 'EMPIRE'],
                ['value' => 'JEDI'],
            ],
        ],
    ],
    'Character' => [
        'type' => 'interface',
        'config' => [
            'fields' => [
                'id' => ['type' => 'ID!'],
                'name' => ['type' => 'String!'],
                'friends' => ['type' => '[Character]'],
                'appearsIn' => ['type' => '[Episode]!'],
            ],
        ],
    ],
    'Human' => [
        'type' => 'object',
        'config' => [
            'fields' => [
                'id' => ['type' => 'ID!'],
                'name' => ['type' => 'String!'],
                'friends' => ['type' => '[Character]'],
                'appearsIn' => ['type' => '[Episode]!'],
                'starships' => ['type' => '[Starship]'],
                'totalCredits' => ['type' => 'Int'],
            ],
            'interfaces' => ['Character'],
        ],
    ],
    'Droid' => [
        'type' => 'object',
        'config' => [
            'fields' => [
                'id' => ['type' => 'ID!'],
                'name' => ['type' => 'String!'],
                'friends' => ['type' => '[Character]'],
                'appearsIn' => ['type' => '[Episode]!'],
                'primaryFunction' => ['type' => 'String'],
            ],
            'interfaces' => ['Character'],
        ],
    ],
    'SearchResult' => [
        'type' => 'union',
        'config' => [
            'types' => ['Human', 'Droid', 'Starship'],
        ],
    ],
    'ReviewInput' => [
        'type' => 'input-object',
        'config' => [
            'fields' => [
                'stars' => ['type' => 'Int!', 'defaultValue' => 5],
                'commentary' => ['type' => 'String', 'defaultValue' => null],
            ],
        ],
    ],
];
