<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\SchemaLanguage;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class SchemaLanguageTest extends TestCase
{
    public function testQueryHumans(): void
    {
        $query = <<<'QUERY'
        { humans {id name direwolf {id name} } }
        QUERY;

        $expected = [
            'data' => [
                'humans' => [
                    [
                        'id' => '1',
                        'name' => 'Jon Snow',
                        'direwolf' => ['id' => '7', 'name' => 'Ghost'],
                    ],
                    [
                        'id' => '2',
                        'name' => 'Arya',
                        'direwolf' => ['id' => '8', 'name' => 'Nymeria'],
                    ],
                    [
                        'id' => '3',
                        'name' => 'Bran',
                        'direwolf' => ['id' => '9', 'name' => 'Summer'],
                    ],
                    [
                        'id' => '4',
                        'name' => 'Rickon',
                        'direwolf' => ['id' => '10', 'name' => 'Shaggydog'],
                    ],
                    [
                        'id' => '5',
                        'name' => 'Robb',
                        'direwolf' => ['id' => '11', 'name' => 'Grey Wind'],
                    ],
                    [
                        'id' => '6',
                        'name' => 'Sansa',
                        'direwolf' => ['id' => '12', 'name' => 'Lady'],
                    ],
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage');
    }

    public function testQueryDirewolves(): void
    {
        $query = <<<'QUERY'
{ direwolves {name status} }
QUERY;

        $expected = [
            'data' => [
                'direwolves' => [
                    ['name' => 'Ghost', 'status' => 'ALIVE'],
                    ['name' => 'Nymeria', 'status' => 'ALIVE'],
                    ['name' => 'Summer', 'status' => 'DECEASED'],
                    ['name' => 'Shaggydog', 'status' => 'DECEASED'],
                    ['name' => 'Grey Wind', 'status' => 'DECEASED'],
                    ['name' => 'Lady', 'status' => 'DECEASED'],
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage');
    }

    public function testQueryACharacter(): void
    {
        $query = <<<'QUERY'
{
  character(id: 1) {
    name
    ...on Human {
      dateOfBirth
    }
  }
}
QUERY;

        $expected = [
            'data' => [
                'character' => [
                    'name' => 'Jon Snow',
                    'dateOfBirth' => '281 AC',
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage');
    }

    public function testQueryHumanByDateOfBirth(): void
    {
        $query = <<<'QUERY'
{
  findHumansByDateOfBirth(years: ["281 AC", "288 AC"]) {
    name
    dateOfBirth
  }
}
QUERY;

        $expected = [
            'data' => [
                'findHumansByDateOfBirth' => [
                    [
                        'name' => 'Jon Snow',
                        'dateOfBirth' => '281 AC',
                    ],
                    [
                        'name' => 'Bran',
                        'dateOfBirth' => '288 AC',
                    ],
                    [
                        'name' => 'Robb',
                        'dateOfBirth' => '281 AC',
                    ],
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage');
    }

    public function testQueryHumanByDateOfBirthUsingVariables(): void
    {
        $query = <<<'QUERY'
query ($years: [Year!]!) {
  findHumansByDateOfBirth(years: $years) {
    name
    dateOfBirth
  }
}
QUERY;

        $expected = [
            'data' => [
                'findHumansByDateOfBirth' => [
                    [
                        'name' => 'Bran',
                        'dateOfBirth' => '288 AC',
                    ],
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage', null, ['years' => ['288 AC']]);
    }

    public function testMutation(): void
    {
        $query = <<<'QUERY'
mutation { resurrectZigZag {name status} }
QUERY;

        $expected = [
            'data' => [
                'resurrectZigZag' => [
                    'name' => 'Rickon',
                    'status' => 'ALIVE',
                ],
            ],
        ];

        $this->assertResponse($query, $expected, static::ANONYMOUS_USER, 'schemaLanguage');
    }
}
