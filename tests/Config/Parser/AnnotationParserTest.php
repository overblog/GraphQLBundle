<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;

class AnnotationParserTest extends TestCase
{
    protected $config = [];

    public function setUp(): void
    {
        parent::setup();

        $configs = [
            'definitions' => [
                'schema' => [
                    'default' => ['query' => 'RootQuery', 'mutation' => 'RootMutation'],
                ],
            ],
            'doctrine' => [
                'types_mapping' => [
                    'text[]' => '[String]',
                ],
            ],
        ];

        $files = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/fixtures/annotations/'));
        foreach ($rii as $file) {
            if (!$file->isDir() && '.php' === \substr($file->getPathname(), -4)) {
                $files[] = $file->getPathname();
            }
        }

        AnnotationParser::clear();

        foreach ($files as $file) {
            AnnotationParser::preParse(new \SplFileInfo($file), $this->containerBuilder, $configs);
        }

        $this->config = [];
        foreach ($files as $file) {
            $this->config += self::cleanConfig(AnnotationParser::parse(new \SplFileInfo($file), $this->containerBuilder, $configs));
        }
    }

    private function expect($name, $type, $config = []): void
    {
        $expected = [
            'type' => $type,
            'config' => $config,
        ];

        $this->assertArrayHasKey($name, $this->config, \sprintf("The GraphQL type '%s' doesn't exist", $name));
        $this->assertEquals($expected, $this->config[$name]);
    }

    public function testTypes(): void
    {
        // Test an interface
        $this->expect('Character', 'interface', [
            'description' => 'The character interface',
            'resolveType' => "@=resolver('character_type', [value])",
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\\\MyResolver::getFriends')"],
            ],
        ]);

        // Test a type extending an interface
        $this->expect('Hero', 'object', [
            'description' => 'The Hero type',
            'interfaces' => ['Character'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\\\MyResolver::getFriends')"],
                'race' => ['type' => 'Race'],
            ],
        ]);

        $this->expect('Droid', 'object', [
            'description' => 'The Droid type',
            'interfaces' => ['Character'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\\\MyResolver::getFriends')"],
                'memory' => ['type' => 'Int!'],
            ],
        ]);

        // Test a type with public/access on fields, methods as field
        $this->expect('Sith', 'object', [
            'description' => 'The Sith type',
            'interfaces' => ['Character'],
            'fieldsDefaultPublic' => '@=isAuthenticated()',
            'fieldsDefaultAccess' => '@=isAuthenticated()',
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=resolver('App\\\\MyResolver::getFriends')"],
                'realName' => ['type' => 'String!', 'access' => "@=hasRole('SITH_LORD')"],
                'location' => ['type' => 'String!', 'public' => "@=hasRole('SITH_LORD')"],
                'currentMaster' => ['type' => 'Sith', 'resolve' => "@=service('master_resolver').getMaster(value)"],
                'victims' => [
                    'type' => '[Character]',
                    'args' => ['jediOnly' => ['type' => 'Boolean', 'description' => 'Only Jedi victims']],
                    'resolve' => "@=value.getVictims(args['jediOnly'])",
                ],
            ],
        ]);

        // Test a type with a field builder
        $this->expect('Planet', 'object', [
            'description' => 'The Planet type',
            'fields' => [
                'name' => ['type' => 'String!'],
                'location' => ['type' => 'GalaxyCoordinates'],
                'population' => ['type' => 'Int!'],
                'notes' => [
                    'builder' => 'NoteFieldBuilder',
                    'builderConfig' => ['option1' => 'value1'],
                ],
                'closestPlanet' => [
                    'type' => 'Planet',
                    'argsBuilder' => [
                        'builder' => 'PlanetFilterArgBuilder',
                        'config' => ['option2' => 'value2'],
                    ],
                    'resolve' => "@=resolver('closest_planet', [args['filter']])",
                ],
            ],
        ]);
    }

    public function testInput(): void
    {
        $this->expect('PlanetInput', 'input-object', [
            'description' => 'Planet Input type description',
            'fields' => [
                'name' => ['type' => 'String!'],
                'population' => ['type' => 'Int!'],
            ],
        ]);
    }

    public function testEnum(): void
    {
        $this->expect('Race', 'enum', [
            'description' => 'The list of races!',
            'values' => [
                'HUMAIN' => ['value' => 1],
                'CHISS' => ['value' => '2', 'description' => 'The Chiss race'],
                'ZABRAK' => ['value' => '3', 'deprecationReason' => 'The Zabraks have been wiped out'],
                'TWILEK' => ['value' => '4'],
            ],
        ]);
    }

    public function testUnion(): void
    {
        $this->expect('SearchResult', 'union', [
            'description' => 'A search result',
            'types' => ['Hero', 'Droid', 'Sith'],
        ]);
    }

    public function testScalar(): void
    {
        $this->expect('GalaxyCoordinates', 'custom-scalar', [
            'serialize' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates', 'serialize'],
            'parseValue' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates', 'parseValue'],
            'parseLiteral' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates', 'parseLiteral'],
            'description' => 'The galaxy coordinates scalar',
        ]);
    }

    public function testProviders(): void
    {
        $this->expect('RootQuery', 'object', [
            'fields' => [
                'searchPlanet' => [
                    'type' => '[Planet]',
                    'args' => ['keyword' => ['type' => 'String!']],
                    'resolve' => "@=service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').searchPlanet(args['keyword'])",
                ],
            ],
        ]);

        $this->expect('RootMutation', 'object', [
            'fields' => [
                'createPlanet' => [
                    'type' => 'Planet',
                    'args' => ['planetInput' => ['type' => 'PlanetInput!']],
                    'resolve' => "@=service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').createPlanet(input('PlanetInput!', args['planetInput']))",
                ],
            ],
        ]);
    }

    public function testDoctrineGuessing(): void
    {
        $this->expect('Lightsaber', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
                'size' => ['type' => 'Int'],
                'holders' => ['type' => '[Hero]!'],
                'creator' => ['type' => 'Hero!'],
                'crystal' => ['type' => 'Crystal!'],
                'battles' => ['type' => '[Battle]!'],
                'currentHolder' => ['type' => 'Hero'],
                'tags' => ['type' => '[String]!'],
            ],
        ]);
    }

    public function testArgsAndReturnGuessing(): void
    {
        $this->expect('Battle', 'object', [
            'fields' => [
                'planet' => ['type' => 'Planet'],
                'casualties' => [
                    'type' => 'Int!',
                    'args' => [
                        'areaId' => ['type' => 'Int!'],
                        'raceId' => ['type' => 'String!'],
                        'dayStart' => ['type' => 'Int', 'default' => null],
                        'dayEnd' => ['type' => 'Int', 'default' => null],
                        'nameStartingWith' => ['type' => 'String', 'default' => ''],
                        'planetId' => ['type' => 'String', 'default' => null],
                    ],
                    'resolve' => "@=value.getCasualties(args['areaId'], args['raceId'], args['dayStart'], args['dayEnd'], args['nameStartingWith'], args['planetId'])",
                ],
            ],
        ]);
    }
}
