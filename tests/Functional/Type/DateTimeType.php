<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Type;

use DateTime;
use Exception;
use GraphQL\Language\AST\StringValueNode;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;

class DateTimeType
{
    /**
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

    /**
     * @param mixed $_
     */
    public static function getDateTime($_, ArgumentInterface $args): ?DateTime
    {
        return $args['dateTime'] ?? new DateTime('2016-11-28 12:00:00');
    }
}
