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

use Symfony\Component\Process\ProcessBuilder;

class StarWarsQueryTest extends AbstractStarWarsTest
{
    /**
     * @group cs
     */
    public function testSchemaCodeStandard()
    {
        $this->assertCodeStandard($this->tmpDir, 'psr2', 'short_array_syntax');
    }

    // Star Wars Query Tests
    // Basic Queries
    public function testCorrectlyIdentifiesR2D2AsTheHeroOfTheStarWarsSaga()
    {
        // Correctly identifies R2-D2 as the hero of the Star Wars Saga
        $query = '
        query HeroNameQuery {
          hero {
            name
          }
        }
        ';
        $expected = [
            'hero' => [
                'name' => 'R2-D2'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testAllowsUsToQueryForTheIDAndFriendsOfR2D2()
    {
        $query = '
        query HeroNameAndFriendsQuery {
          hero {
            id
            name
            friends {
              name
            }
          }
        }
        ';
        $expected = [
            'hero' => [
                'id' => '2001',
                'name' => 'R2-D2',
                'friends' => [
                    [
                        'name' => 'Luke Skywalker',
                    ],
                    [
                        'name' => 'Han Solo',
                    ],
                    [
                        'name' => 'Leia Organa',
                    ],
                ]
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // Nested Queries
    public function testAllowsUsToQueryForTheFriendsOfFriendsOfR2D2()
    {
        $query = '
        query NestedQuery {
          hero {
            name
            friends {
              name
              appearsIn
              friends {
                name
              }
            }
          }
        }
        ';
        $expected = [
            'hero' => [
                'name' => 'R2-D2',
                'friends' => [
                    [
                        'name' => 'Luke Skywalker',
                        'appearsIn' => ['NEWHOPE', 'EMPIRE', 'JEDI',],
                        'friends' => [
                            ['name' => 'Han Solo',],
                            ['name' => 'Leia Organa',],
                            ['name' => 'C-3PO',],
                            ['name' => 'R2-D2',],
                        ],
                    ],
                    [
                        'name' => 'Han Solo',
                        'appearsIn' => ['NEWHOPE', 'EMPIRE', 'JEDI'],
                        'friends' => [
                            ['name' => 'Luke Skywalker',],
                            ['name' => 'Leia Organa'],
                            ['name' => 'R2-D2',],
                        ]
                    ],
                    [
                        'name' => 'Leia Organa',
                        'appearsIn' => ['NEWHOPE', 'EMPIRE', 'JEDI'],
                        'friends' =>
                            [
                                ['name' => 'Luke Skywalker',],
                                ['name' => 'Han Solo',],
                                ['name' => 'C-3PO',],
                                ['name' => 'R2-D2',],
                            ],
                    ],
                ],
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // Using IDs and query parameters to refetch objects
    public function testAllowsUsToQueryForLukeSkywalkerDirectlyUsingHisID()
    {
        $query = '
        query FetchLukeQuery {
          human(id: "1000") {
            name
          }
        }
        ';
        $expected = [
            'human' => [
                'name' => 'Luke Skywalker'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }
    public function testGenericQueryToGetLukeSkywalkerById()
    {
        // Allows us to create a generic query, then use it to fetch Luke Skywalker using his ID
        $query = '
        query FetchSomeIDQuery($someId: String!) {
          human(id: $someId) {
            name
          }
        }
        ';
        $params = [
            'someId' => '1000'
        ];
        $expected = [
            'human' => [
                'name' => 'Luke Skywalker'
            ]
        ];
        $this->assertValidQuery($query, $expected, $params);
    }

    public function testGenericQueryToGetHanSoloById()
    {
        // Allows us to create a generic query, then use it to fetch Han Solo using his ID
        $query = '
        query FetchSomeIDQuery($someId: String!) {
          human(id: $someId) {
            name
          }
        }
        ';
        $params = [
            'someId' => '1002'
        ];
        $expected = [
            'human' => [
                'name' => 'Han Solo'
            ]
        ];
        $this->assertValidQuery($query, $expected, $params);
    }

    public function testGenericQueryWithInvalidId()
    {
        // Allows us to create a generic query, then pass an invalid ID to get null back
        $query = '
        query humanQuery($id: String!) {
          human(id: $id) {
            name
          }
        }
        ';
        $params = [
            'id' => 'not a valid id'
        ];
        $expected = [
            'human' => null
        ];
        $this->assertValidQuery($query, $expected, $params);
    }

    // Using aliases to change the key in the response
    public function testLukeKeyAlias()
    {
        // Allows us to query for Luke, changing his key with an alias
        $query = '
        query FetchLukeAliased {
          luke: human(id: "1000") {
            name
          }
        }
        ';
        $expected = [
            'luke' => [
                'name' => 'Luke Skywalker'
            ],
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testTwoRootKeysAsAnAlias()
    {
        // Allows us to query for both Luke and Leia, using two root fields and an alias
        $query = '
        query FetchLukeAndLeiaAliased {
          luke: human(id: "1000") {
            name
          }
          leia: human(id: "1003") {
            name
          }
        }
        ';
        $expected = [
            'luke' => [
                'name' => 'Luke Skywalker'
            ],
            'leia' => [
                'name' => 'Leia Organa'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // Uses fragments to express more complex queries
    public function testQueryUsingDuplicatedContent()
    {
        // Allows us to query using duplicated content
        $query = '
        query DuplicateFields {
          luke: human(id: "1000") {
            name
            homePlanet
          }
          leia: human(id: "1003") {
            name
            homePlanet
          }
        }
        ';
        $expected = [
            'luke' => [
                'name' => 'Luke Skywalker',
                'homePlanet' => 'Tatooine'
            ],
            'leia' => [
                'name' => 'Leia Organa',
                'homePlanet' => 'Alderaan'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testUsingFragment()
    {
        // Allows us to use a fragment to avoid duplicating content
        $query = '
        query UseFragment {
          luke: human(id: "1000") {
            ...HumanFragment
          }
          leia: human(id: "1003") {
            ...HumanFragment
          }
        }
        fragment HumanFragment on Human {
          name
          homePlanet
        }
        ';
        $expected = [
            'luke' => [
                'name' => 'Luke Skywalker',
                'homePlanet' => 'Tatooine'
            ],
            'leia' => [
                'name' => 'Leia Organa',
                'homePlanet' => 'Alderaan'
            ]
        ];
        $this->assertValidQuery($query, $expected);
    }

    // Using __typename to find the type of an object
    public function testVerifyThatR2D2IsADroid()
    {
        $query = '
        query CheckTypeOfR2 {
          hero {
            __typename
            name
          }
        }
        ';
        $expected = [
            'hero' => [
                '__typename' => 'Droid',
                'name' => 'R2-D2'
            ],
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testVerifyThatLukeIsHuman()
    {
        $query = '
        query CheckTypeOfLuke {
          hero(episode: EMPIRE) {
            __typename
            name
          }
        }
        ';
        $expected = [
            'hero' => [
                '__typename' => 'Human',
                'name' => 'Luke Skywalker'
            ],
        ];
        $this->assertValidQuery($query, $expected);
    }

    public function testDateTime()
    {
        $query = '{ dateTime }';
        $expected = [
            'dateTime' => '2016-11-28 12:00:00',
        ];
        $this->assertValidQuery($query, $expected);

        $query = '{ dateTime(dateTime: "2016-01-18 23:00:00") }';
        $expected = [
            'dateTime' => '2016-01-18 23:00:00',
        ];
        $this->assertValidQuery($query, $expected);
    }
}
