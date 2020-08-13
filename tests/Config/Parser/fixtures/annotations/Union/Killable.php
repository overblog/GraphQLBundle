<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Union;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Union(resolveType="value.getType()")
 */
interface Killable
{
}
