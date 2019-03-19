<?php declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

/**
 * Class StarWarsData
 *
 * This is a copy of original test file GraphQL\Tests\StarWarsData
 *
 */
class StarWarsData
{
    private static function luke(): array
    {
        return [
            'id' => '1000',
            'name' => 'Luke Skywalker',
            'friends' => ['1002', '1003', '2000', '2001'],
            'appearsIn' => [4, 5, 6],
            'homePlanet' => 'Tatooine',
        ];
    }

    private static function vader(): array
    {
        return [
            'id' => '1001',
            'name' => 'Darth Vader',
            'friends' => ['1004'],
            'appearsIn' => [4, 5, 6],
            'homePlanet' => 'Tatooine',
        ];
    }

    private static function han(): array
    {
        return [
            'id' => '1002',
            'name' => 'Han Solo',
            'friends' => ['1000', '1003', '2001'],
            'appearsIn' => [4, 5, 6],
        ];
    }

    private static function leia(): array
    {
        return [
            'id' => '1003',
            'name' => 'Leia Organa',
            'friends' => ['1000', '1002', '2000', '2001'],
            'appearsIn' => [4, 5, 6],
            'homePlanet' => 'Alderaan',
        ];
    }

    private static function tarkin(): array
    {
        return [
            'id' => '1004',
            'name' => 'Wilhuff Tarkin',
            'friends' => ['1001'],
            'appearsIn' => [4],
        ];
    }

    public static function humans(): array
    {
        return [
            '1000' => self::luke(),
            '1001' => self::vader(),
            '1002' => self::han(),
            '1003' => self::leia(),
            '1004' => self::tarkin(),
        ];
    }

    private static function threepio(): array
    {
        return [
            'id' => '2000',
            'name' => 'C-3PO',
            'friends' => ['1000', '1002', '1003', '2001'],
            'appearsIn' => [4, 5, 6],
            'primaryFunction' => 'Protocol',
        ];
    }

    /**
     * We export artoo directly because the schema returns him
     * from a root field, and hence needs to reference him.
     */
    public static function artoo(): array
    {
        return [

            'id' => '2001',
            'name' => 'R2-D2',
            'friends' => ['1000', '1002', '1003'],
            'appearsIn' => [4, 5, 6],
            'primaryFunction' => 'Astromech',
        ];
    }

    public static function droids(): array
    {
        return [
            '2000' => self::threepio(),
            '2001' => self::artoo(),
        ];
    }

    /**
     * Helper function to get a character by ID.
     */
    public static function getCharacter(string $id): array
    {
        $humans = self::humans();
        $droids = self::droids();
        if (isset($humans[$id])) {
            return $humans[$id];
        }
        if (isset($droids[$id])) {
            return $droids[$id];
        }
        return null;
    }

    /**
     * Allows us to query for a character's friends.
     */
    public static function getFriends(array $character): array
    {
        return \array_map([__CLASS__, 'getCharacter'], $character['friends']);
    }

    public static function getHero(?int $episode): array
    {
        if ($episode === 5) {
            // Luke is the hero of Episode V.
            return self::luke();
        }
        // Artoo is the hero otherwise.
        return self::artoo();
    }

    public static function getHuman(string $id): ?array
    {
        $humans = self::humans();

        return $humans[$id] ?? null;
    }

    public static function getDroid($id): ?array
    {
        $droids = self::droids();

        return $droids[$id] ?? null;
    }
}
