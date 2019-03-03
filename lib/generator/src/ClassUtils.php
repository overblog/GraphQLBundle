<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator;

abstract class ClassUtils
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function shortenClassName($definition)
    {
        $shortName = substr($definition, strrpos($definition, '\\') + 1);

        return $shortName;
    }

    public static function shortenClassFromCode($code, callable $callback = null)
    {
        if (null === $callback) {
            $callback = function ($matches) {
                return static::shortenClassName($matches[1]);
            };
        }

        $codeParsed = preg_replace_callback('@((?:\\\\{1,2}\w+|\w+\\\\{1,2})(?:\w+\\\\{0,2})+)@', $callback, $code);

        return $codeParsed;
    }

    public static function cleanClasseName($use)
    {
        return ltrim($use, '\\');
    }
}
