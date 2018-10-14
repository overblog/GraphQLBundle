<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum(values={@GQL\EnumValue(name="TATOUINE", description="The planet of Tatouine")})
 * @GQL\Description("The list of planets!")
 */
class Planet
{
    const DAGOBAH = 1;
    const TATOUINE = "2";
    const HOTH = "3";
    const BESPIN = "4";
}
