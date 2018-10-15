<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum(values={@GQL\EnumValue(name="TATOUINE", description="The planet of Tatouine")})
 * @GQL\Description("The list of planets!")
 */
class Planet
{
    public const DAGOBAH = 1;
    public const TATOUINE = '2';
    public const HOTH = '3';
    public const BESPIN = '4';
}
