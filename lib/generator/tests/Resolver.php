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

use GraphQL\Type\Definition\Type;

abstract class Resolver
{
    /** @var Type */
    private static $humanType;

    /** @var Type */
    private static $droidType;

    private function __construct()
    {
    }

    public static function getHumanType(): Type
    {
        return self::$humanType;
    }

    public static function getDroidType(): Type
    {
        return self::$droidType;
    }

    /**
     * @param Type $humanType
     */
    public static function setHumanType($humanType): void
    {
        self::$humanType = $humanType;
    }

    /**
     * @param Type $droidType
     */
    public static function setDroidType($droidType): void
    {
        self::$droidType = $droidType;
    }

    public static function resolveType($obj): ?Type
    {
        $humans = StarWarsData::humans();
        $droids = StarWarsData::droids();
        if (isset($humans[$obj['id']])) {
            return static::getHumanType();
        }
        if (isset($droids[$obj['id']])) {
            return static::getDroidType();
        }
        return null;
    }

    public static function getFriends($droidOrHuman): array
    {
        return StarWarsData::getFriends($droidOrHuman);
    }

    public static function getHero($root, $args): array
    {
        return StarWarsData::getHero($args['episode']['name'] ?? null);
    }

    public static function getHuman($root, $args): ?array
    {
        $humans = StarWarsData::humans();

        return $humans[$args['id']] ?? null;
    }

    public static function getDroid($root, $args): ?array
    {
        $droids = StarWarsData::droids();

        return $droids[$args['id']] ?? null;
    }

    public static function getDateTime($root, $args): ?\DateTime
    {
        return $args['dateTime'] ?? new \DateTime('2016-11-28 12:00:00');
    }
}
