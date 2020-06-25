<?php

declare(strict_types=1);

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Type;

use DateTime;
use Exception;
use GraphQL\Language\AST\StringValueNode;

class DateTimeType
{
    /**
     * @param DateTime $value
     *
     * @return string
     */
    public static function serialize(DateTime $value)
    {
        return $value->format('Y-m-d H:i:s');
    }

    /**
     * @param mixed $value
     *
     * @throws Exception
     */
    public static function parseValue($value): DateTime
    {
        return new DateTime($value);
    }

    /**
     * @param StringValueNode $valueNode
     *
     * @throws Exception
     */
    public static function parseLiteral($valueNode): DateTime
    {
        return new DateTime($valueNode->value);
    }

    public static function getDateTime($root, $args): ?DateTime
    {
        return $args['dateTime'] ?? new \DateTime('2016-11-28 12:00:00');
    }
}
