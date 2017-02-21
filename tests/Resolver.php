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

use GraphQL\Tests\StarWarsData;
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

    public static function getHumanType()
    {
        return self::$humanType;
    }

    public static function getDroidType()
    {
        return self::$droidType;
    }

    /**
     * @param Type $humanType
     */
    public static function setHumanType($humanType)
    {
        self::$humanType = $humanType;
    }

    /**
     * @param Type $droidType
     */
    public static function setDroidType($droidType)
    {
        self::$droidType = $droidType;
    }

    public static function resolveType($obj)
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

    public static function getFriends($droidOrHuman)
    {
        return StarWarsData::getFriends($droidOrHuman);
    }

    public static function getHero($root, $args)
    {
        return StarWarsData::getHero(isset($args['episode']['name']) ? $args['episode']['name'] : null);
    }

    public static function getHuman($root, $args)
    {
        $humans = StarWarsData::humans();
        return isset($humans[$args['id']]) ? $humans[$args['id']] : null;
    }

    public static function getDroid($root, $args)
    {
        $droids = StarWarsData::droids();
        return isset($droids[$args['id']]) ? $droids[$args['id']] : null;
    }

    public static function getDateTime($root, $args)
    {
        return isset($args['dateTime']) ? $args['dateTime'] : new \DateTime('2016-11-28 12:00:00');
    }
}
