<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum
 */
#[GQL\Enum]
enum Color
{
    case RED;
    case GREEN;
    case BLUE;
    case YELLOW;
}
