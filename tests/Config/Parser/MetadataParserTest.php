<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Column;
use Exception;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum\Color;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use function sprintf;
use function substr;

abstract class MetadataParserTest extends TestCase
{
    protected array $config = [];
    protected array $ignoredPaths = ['Invalid', 'Deprecated'];

    protected array $parserConfig = [
        'definitions' => [
            'schema' => [
                'default' => ['query' => 'RootQuery', 'mutation' => 'RootMutation'],
                'second' => ['query' => 'RootQuery2', 'mutation' => 'RootMutation2'],
            ],
        ],
        'doctrine' => [
            'types_mapping' => [
                'text[]' => '[String]',
            ],
        ],
    ];

    /**
     * @param array $args
     *
     * @return mixed
     */
    abstract public function parser(string $method, ...$args);

    abstract public function formatMetadata(string $metadata): string;

    public function setUp(): void
    {
        parent::setup();

        if (!self::isDoctrineAnnotationInstalled()) {
            $this->markTestSkipped('doctrine/annotations are not installed');
        }

        $files = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/fixtures/annotations/'));
        foreach ($rii as $file) {
            if (!$file->isDir() && '.php' === substr($file->getPathname(), -4)) {
                foreach ($this->ignoredPaths as $ignoredPath) {
                    if (str_contains($file->getPathName(), $ignoredPath)) {
                        continue 2;
                    }
                    if (!self::isDoctrineOrmInstalled() && 'Lightsaber.php' === $file->getFileName()) {
                        continue 2;
                    }
                    if (PHP_VERSION_ID < 80100 && 'Color.php' === $file->getFileName()) {
                        continue 2;
                    }
                }

                $files[] = $file->getPathname();
            }
        }
        $this->parser('reset', $this->parserConfig);

        foreach ($files as $file) {
            $this->parser('preParse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
        }

        $this->config = [];
        foreach ($files as $file) {
            $this->config += self::cleanConfig($this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig));
        }
    }

    public static function isDoctrineAnnotationInstalled(): bool
    {
        return interface_exists(Reader::class);
    }

    public static function isDoctrineOrmInstalled(): bool
    {
        return class_exists(Column::class);
    }

    protected function expect(string $name, string $type, array $config = []): void
    {
        $expected = [
            'type' => $type,
            'config' => $config,
        ];

        $this->assertArrayHasKey($name, $this->config, sprintf("The GraphQL type '%s' doesn't exist", $name));
        $this->assertEquals($expected, $this->config[$name]);
    }

    public function testExceptionIfRegisterSameType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Failed to parse GraphQL metadata from file/');
        $this->parser('preParse', new SplFileInfo(__DIR__.'/fixtures/annotations/Type/Battle.php'), $this->containerBuilder, ['doctrine' => ['types_mapping' => []]]);
    }

    public function testTypes(): void
    {
        // Test an interface
        $this->expect('Character', 'interface', [
            'description' => 'The character interface',
            'resolveType' => "@=query('character_type', [value])",
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=query('App\\MyResolver::getFriends')"],
            ],
        ]);

        // Test a type extending an interface
        $this->expect('Hero', 'object', [
            'description' => 'The Hero type',
            'interfaces' => ['Character'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=query('App\\MyResolver::getFriends')"],
                'race' => ['type' => 'Race'],
            ],
        ]);

        $this->expect('Droid', 'object', [
            'description' => 'The Droid type',
            'interfaces' => ['Character'],
            'isTypeOf' => "@=isTypeOf('App\Entity\Droid')",
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=query('App\\MyResolver::getFriends')"],
                'memory' => ['type' => 'Int!'],
                'planet_allowedPlanets' => [
                    'type' => '[Planet]',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository\').getAllowedPlanetsForDroids, arguments({}, args))',
                    'access' => '@=override_access',
                    'public' => '@=default_public',
                ],
                'planet_armorResistance' => [
                    'type' => 'Int!',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);

        // Test a type with public/access on fields, methods as field
        $this->expect('Sith', 'object', [
            'description' => 'The Sith type',
            'interfaces' => ['Character'],
            'resolveField' => '@=value',
            'fieldsDefaultPublic' => '@=isAuthenticated()',
            'fieldsDefaultAccess' => '@=isAuthenticated()',
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=query('App\\MyResolver::getFriends')"],
                'realName' => ['type' => 'String!', 'access' => "@=hasRole('SITH_LORD')"],
                'location' => ['type' => 'String!', 'public' => "@=hasRole('SITH_LORD')"],
                'currentMaster' => ['type' => 'Sith', 'resolve' => "@=service('master_resolver').getMaster(value)"],
                'victims' => [
                    'type' => '[Character]',
                    'args' => ['jediOnly' => ['type' => 'Boolean', 'description' => 'Only Jedi victims', 'defaultValue' => false]],
                    'resolve' => '@=call(value.getVictims, arguments({jediOnly: "Boolean"}, args))',
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
                    'resolve' => "@=query('closest_planet', [args['filter']])",
                ],
            ],
        ]);

        // Test a type with a fields builder
        $this->expect('Crystal', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
            ],
            'builders' => [['builder' => 'MyFieldsBuilder', 'builderConfig' => ['param1' => 'val1']]],
        ]);

        // Test a type extending another type
        $this->expect('Cat', 'object', [
            'description' => 'The Cat type',
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the animal'],
                'lives' => ['type' => 'Int!'],
                'toys' => ['type' => '[String!]!'],
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
                'description' => ['type' => 'String!'],
                'diameter' => ['type' => 'Int'],
                'variable' => ['type' => 'Int!'],
                'tags' => ['type' => '[String]!'],
                'alienInvasion' => ['type' => 'Boolean!', 'deprecationReason' => 'No more alien invasions on planets'],
            ],
        ]);
    }

    public function testInterfaces(): void
    {
        $this->expect('WithArmor', 'interface', [
            'description' => 'The armored interface',
            'resolveType' => '@=query(\'character_type\', [value])',
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

        if (PHP_VERSION_ID >= 80100) {
            $this->expect('Color', 'enum', [
                'enumClass' => Color::class,
                'values' => [
                    'RED' => ['value' => 'RED', 'description' => 'The color red'],
                    'GREEN' => ['value' => 'GREEN'],
                    'BLUE' => ['value' => 'BLUE'],
                    'YELLOW' => ['value' => 'YELLOW'],
                ],
            ]);
        }
    }

    public function testUnion(): void
    {
        $this->expect('ResultSearch', 'union', [
            'description' => 'A search result',
            'types' => ['Hero', 'Droid', 'Sith'],
            'resolveType' => '@=value.getType()',
        ]);

        $this->expect('SearchResult2', 'union', [
            'types' => ['Hero', 'Droid', 'Sith'],
            'resolveType' => "@=call('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Union\\\\SearchResult2::resolveType', [service('overblog_graphql.type_resolver'), value], true)",
        ]);
    }

    public function testUnionAutoguessed(): void
    {
        $this->expect('Killable', 'union', [
            'types' => ['Hero', 'Mandalorian',  'Sith'],
            'resolveType' => '@=value.getType()',
        ]);
    }

    public function testInterfaceAutoguessed(): void
    {
        $this->expect('Mandalorian', 'object', [
            'interfaces' => ['Character', 'WithArmor'],
            'fields' => [
                'name' => ['type' => 'String!', 'description' => 'The name of the character'],
                'friends' => ['type' => '[Character]', 'description' => 'The friends of the character', 'resolve' => "@=query('App\\MyResolver::getFriends')"],
                'planet_armorResistance' => [
                    'type' => 'Int!',
                    'resolve' => '@=call(service(\'Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository\').getArmorResistance, arguments({}, args))',
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);
    }

    public function testScalar(): void
    {
        $this->expect('GalaxyCoordinates', 'custom-scalar', [
            'serialize' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\\annotations\Scalar\GalaxyCoordinates', 'serialize'],
            'parseValue' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\\annotations\Scalar\GalaxyCoordinates', 'parseValue'],
            'parseLiteral' => ['Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\\annotations\Scalar\GalaxyCoordinates', 'parseLiteral'],
            'description' => 'The galaxy coordinates scalar',
        ]);
    }

    public function testProviders(): void
    {
        $this->expect('RootQuery', 'object', [
            'fields' => [
                'planet_searchPlanet' => [
                    'type' => '[Planet]',
                    'args' => ['keyword' => ['type' => 'String!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').searchPlanet, arguments({keyword: \"String!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'planet_isPlanetDestroyed' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'countSecretWeapons' => [
                    'type' => 'Int!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\WeaponRepository').countSecretWeapons, arguments({}, args))",
                ],
                'planet_searchStar' => [
                    'type' => '[Planet]',
                    'args' => ['distance' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').searchStar, arguments({distance: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);

        $this->expect('RootMutation', 'object', [
            'fields' => [
                'planet_createPlanet' => [
                    'type' => 'Planet',
                    'args' => ['planetInput' => ['type' => 'PlanetInput!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').createPlanet, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=override_public',
                ],
                'planet_destroyPlanet' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
            ],
        ]);
    }

    public function testProvidersMultischema(): void
    {
        $this->expect('RootQuery2', 'object', [
            'fields' => [
                'planet_getPlanetSchema2' => [
                    'type' => 'Planet',
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').getPlanetSchema2, arguments({}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'planet_isPlanetDestroyed' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').isPlanetDestroyed, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'hasSecretWeapons' => [
                    'type' => 'Boolean!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\WeaponRepository').hasSecretWeapons, arguments({}, args))",
                ],
            ],
        ]);

        $this->expect('RootMutation2', 'object', [
            'fields' => [
                'planet_createPlanetSchema2' => [
                    'type' => 'Planet',
                    'args' => ['planetInput' => ['type' => 'PlanetInput!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').createPlanetSchema2, arguments({planetInput: \"PlanetInput!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=override_public',
                ],
                'planet_destroyPlanet' => [
                    'type' => 'Boolean!',
                    'args' => ['planetId' => ['type' => 'Int!']],
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\PlanetRepository').destroyPlanet, arguments({planetId: \"Int!\"}, args))",
                    'access' => '@=default_access',
                    'public' => '@=default_public',
                ],
                'createLightsaber' => [
                    'type' => 'Boolean!',
                    'resolve' => "@=call(service('Overblog\\\\GraphQLBundle\\\\Tests\\\\Config\\\\Parser\\\\fixtures\\\\annotations\\\\Repository\\\\WeaponRepository').createLightsaber, arguments({}, args))",
                ],
            ],
        ]);
    }

    public function testDoctrineGuessing(): void
    {
        if (!self::isDoctrineOrmInstalled()) {
            $this->markTestSkipped('doctrine/orm is not installed');
        }

        $this->expect('Lightsaber', 'object', [
            'fields' => [
                'color' => ['type' => 'String!'],
                'size' => ['type' => 'Int'],
                'holders' => ['type' => '[Hero]!'],
                'creator' => ['type' => 'Hero!'],
                'crystal' => ['type' => 'Crystal!'],
                'battles' => ['type' => '[Battle]!'],
                'currentHolder' => ['type' => 'Hero'],
                'tags' => ['type' => '[String]!', 'deprecationReason' => 'No more tags on lightsabers'],
                'text' => ['type' => 'String!'],
                'string' => ['type' => 'String!'],
                'float' => ['type' => 'Float!'],
                'decimal' => ['type' => 'Float!'],
                'bool' => ['type' => 'Boolean!'],
                'boolean' => ['type' => 'Boolean!'],
            ],
        ]);
    }

    public function testArgsAndReturnGuessing(): void
    {
        $this->expect('Battle', 'object', [
            'fields' => [
                'planet' => ['type' => 'Planet', 'complexity' => '@=100 + childrenComplexity'],
                'casualties' => [
                    'type' => 'Int',
                    'args' => [
                        'areaId' => ['type' => 'Int!'],
                        'raceId' => ['type' => 'String!'],
                        'dayStart' => ['type' => 'Int', 'defaultValue' => null],
                        'dayEnd' => ['type' => 'Int', 'defaultValue' => null],
                        'nameStartingWith' => ['type' => 'String', 'defaultValue' => ''],
                        'planet' => ['type' => 'PlanetInput', 'defaultValue' => null],
                        'away' => ['type' => 'Boolean', 'defaultValue' => false],
                        'maxDistance' => ['type' => 'Float', 'defaultValue' => null],
                    ],
                    'resolve' => '@=call(value.getCasualties, arguments({areaId: "Int!", raceId: "String!", dayStart: "Int", dayEnd: "Int", nameStartingWith: "String", planet: "PlanetInput", away: "Boolean", maxDistance: "Float"}, args))',
                    'complexity' => '@=childrenComplexity * 5',
                ],
            ],
        ]);
    }

    public function testRelayConnectionAuto(): void
    {
        $this->expect('EnemiesConnection', 'object', [
            'builders' => [
                ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => 'EnemiesConnectionEdge']],
            ],
        ]);

        $this->expect('EnemiesConnectionEdge', 'object', [
            'builders' => [
                ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => 'Character']],
            ],
        ]);
    }

    public function testRelayConnectionEdge(): void
    {
        $this->expect('FriendsConnection', 'object', [
            'builders' => [
                ['builder' => 'relay-connection', 'builderConfig' => ['edgeType' => 'FriendsConnectionEdge']],
            ],
        ]);

        $this->expect('FriendsConnectionEdge', 'object', [
            'builders' => [
                ['builder' => 'relay-edge', 'builderConfig' => ['nodeType' => 'Character']],
            ],
        ]);
    }

    public function testInvalidParamGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidArgumentGuessing.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('Missing type hint for auto-guessed argument should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/Argument n°1 "\$test"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidReturnGuessing(): void
    {
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidReturnTypeGuessing.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('Missing type hint for auto-guessed return type should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/is missing on method "guessFail" and cannot be auto-guessed from the following type guessers/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineRelationGuessing(): void
    {
        if (!self::isDoctrineOrmInstalled()) {
            $this->markTestSkipped('doctrine/orm is not installed');
        }
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidDoctrineRelationGuessing.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine target class/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidDoctrineTypeGuessing(): void
    {
        if (!self::isDoctrineOrmInstalled()) {
            $this->markTestSkipped('doctrine/orm is not installed');
        }
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidDoctrineTypeGuessing.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('Auto-guessing field type from doctrine relation on a non graphql entity should failed with an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/Unable to auto-guess GraphQL type from Doctrine type "invalidType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidUnion(): void
    {
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidUnion.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('Union with missing resolve type should have raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.$this->formatMetadata('Union').' has no "resolveType"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidAccess(): void
    {
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidAccess.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail('@Access annotation without a @Field annotation should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/The metadatas '.$this->formatMetadata('Access').' and\/or '.$this->formatMetadata('Visible').' defined on "field"/', $e->getPrevious()->getMessage());
        }
    }

    public function testFieldOnPrivateProperty(): void
    {
        try {
            $file = __DIR__.'/fixtures/annotations/Invalid/InvalidPrivateMethod.php';
            $this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
            $this->fail($this->formatMetadata('Access').' annotation without a '.$this->formatMetadata('Field').' annotation should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/The metadata '.$this->formatMetadata('Field').' can only be applied to public method/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderQueryOnMutation(): void
    {
        $file = __DIR__.'/fixtures/annotations/Invalid/InvalidProvider.php';
        $this->parser('preParse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);

        try {
            $mutationFile = __DIR__.'/fixtures/annotations/Type/RootMutation2.php';
            $this->parser('parse', new SplFileInfo($mutationFile), $this->containerBuilder, $this->parserConfig);
            $this->fail('Using @Query or #Query targeting mutation type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/try to add a query field on type "RootMutation2"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidProviderMutationOnQuery(): void
    {
        $file = __DIR__.'/fixtures/annotations/Invalid/InvalidProvider.php';
        $this->parser('preParse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
        try {
            $queryFile = __DIR__.'/fixtures/annotations/Type/RootQuery2.php';
            $this->parser('parse', new SplFileInfo($queryFile), $this->containerBuilder, $this->parserConfig);
            $this->fail('Using @Mutation or #Mutation targeting query type should raise an exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
            $this->assertMatchesRegularExpression('/try to add a mutation on type "RootQuery2"/', $e->getPrevious()->getMessage());
        }
    }

    public function testInvalidPhpFiles(): void
    {
        $files = [
            __DIR__.'/fixtures/annotations/Invalid/HasNoClass.php',
            __DIR__.'/fixtures/annotations/Invalid/EmptyPhpFile.php',
            __DIR__.'/fixtures/annotations/Invalid/NotAPhpFile',
            __DIR__.'/fixtures/annotations/Type/RootQuery.php',
        ];
        $this->parser('reset', $this->parserConfig);

        foreach ($files as $file) {
            $this->parser('preParse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig);
        }

        $config = [];
        foreach ($files as $file) {
            $config += self::cleanConfig($this->parser('parse', new SplFileInfo($file), $this->containerBuilder, $this->parserConfig));
        }

        $this->assertSame([
            'RootQuery' => [
                'type' => 'object',
            ],
        ], $config);
    }
}
