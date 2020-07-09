<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use function array_filter;

class Characters
{
    public const TYPE_HUMAN = 'human';
    public const TYPE_DIREWOLF = 'direwolf';

    private static array $characters = [
        1 => [
            'id' => '1',
            'name' => 'Jon Snow',
            'direwolf' => 7,
            'status' => 1,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 281,
        ],
        2 => [
            'id' => '2',
            'name' => 'Arya',
            'direwolf' => 8,
            'status' => 1,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 287,
        ],
        3 => [
            'id' => '3',
            'name' => 'Bran',
            'direwolf' => 9,
            'status' => 1,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 288,
        ],
        4 => [
            'id' => '4',
            'name' => 'Rickon',
            'direwolf' => 10,
            'status' => 0,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 292,
        ],
        5 => [
            'id' => '5',
            'name' => 'Robb',
            'direwolf' => 11,
            'status' => 0,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 281,
        ],
        6 => [
            'id' => '6',
            'name' => 'Sansa',
            'direwolf' => 12,
            'status' => 1,
            'type' => self::TYPE_HUMAN,
            'dateOfBirth' => 285,
        ],
        7 => [
            'id' => '7',
            'name' => 'Ghost',
            'status' => 1,
            'type' => self::TYPE_DIREWOLF,
        ],
        8 => [
            'id' => '8',
            'name' => 'Nymeria',
            'status' => 1,
            'type' => self::TYPE_DIREWOLF,
        ],
        9 => [
            'id' => '9',
            'name' => 'Summer',
            'status' => 0,
            'type' => self::TYPE_DIREWOLF,
        ],
        10 => [
            'id' => '10',
            'name' => 'Shaggydog',
            'status' => 0,
            'type' => self::TYPE_DIREWOLF,
        ],
        11 => [
            'id' => '11',
            'name' => 'Grey Wind',
            'status' => 0,
            'type' => self::TYPE_DIREWOLF,
        ],
        12 => [
            'id' => '12',
            'name' => 'Lady',
            'status' => 0,
            'type' => self::TYPE_DIREWOLF,
        ],
    ];

    public static function getCharacters(): array
    {
        return self::$characters;
    }

    public static function getHumans(): array
    {
        return self::findByType(self::TYPE_HUMAN);
    }

    public static function getDirewolves(): array
    {
        return self::findByType(self::TYPE_DIREWOLF);
    }

    public static function resurrectZigZag(): array
    {
        $zigZag = self::$characters[4];
        $zigZag['status'] = 1;

        return $zigZag;
    }

    private static function findByType(string $type): array
    {
        return array_filter(self::$characters, function ($character) use ($type) {
            return $type === $character['type'];
        });
    }
}
