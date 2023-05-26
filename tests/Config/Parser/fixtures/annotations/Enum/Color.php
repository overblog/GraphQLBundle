<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum
 *
 * @GQL\EnumValue(name="RED", description="The color red")
 */
#[GQL\Enum]
enum Color
{
    #[GQL\Description('The color red')]
    case RED;

    case GREEN;

    case BLUE;

    case YELLOW;
}
