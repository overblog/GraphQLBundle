<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Scalar;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Scalar
 * @GQL\Description("My custom scalar")
 */
class MyScalar
{
    public static function serialize()
    {
    }
    public static function parseValue()
    {
    }
    public static function parseLiteral()
    {
    }
}
