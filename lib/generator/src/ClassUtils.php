<?php declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator;

use Overblog\GraphQLGenerator\Exception\GeneratorException;

abstract class ClassUtils
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function shortenClassName(string $definition): string
    {
        $position = \strrpos($definition, '\\');

        if ($position === false) {
            throw new GeneratorException(sprintf('Unable to extract the position in %s', $definition));
        }

        $shortName = \substr($definition, $position + 1);

        return $shortName;
    }

    public static function shortenClassFromCode(string $code, callable $callback = null): ?string
    {
        if (null === $callback) {
            $callback = static function (array $matches): string {
                return static::shortenClassName($matches[1]);
            };
        }

        $codeParsed = \preg_replace_callback('@((?:\\\\{1,2}\w+|\w+\\\\{1,2})(?:\w+\\\\{0,2})+)@', $callback, $code);

        return $codeParsed;
    }

    public static function cleanClasseName(string $use): string
    {
        return \ltrim($use, '\\');
    }
}
