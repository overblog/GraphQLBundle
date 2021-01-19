<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\TypeInterface(name="WithArmor", resolveType="@=resolver('character_type', [value])")
 * @GQL\Description("The armored interface")
 */
#[GQL\TypeInterface("WithArmor", resolveType: "@=resolver('character_type', [value])")]
#[GQL\Description("The armored interface")]
interface Armored
{
}
