<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Deprecated;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum(values={
 *      @GQL\EnumValue(name="P1", description="P1 description"),
 *      @GQL\EnumValue(name="P2", deprecationReason="P2 deprecated"),
 * })
 */
class DeprecatedEnum
{
    public const P1 = 1;
    public const P2 = 2;
}
