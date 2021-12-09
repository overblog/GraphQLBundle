<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
final class InvalidAccess
{
    /**
     * @GQL\Access("access")
     */
    #[GQL\Access('access')]
    public string $field;
}
