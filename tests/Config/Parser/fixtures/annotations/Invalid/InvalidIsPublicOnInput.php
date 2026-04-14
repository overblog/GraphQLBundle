<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 */
#[GQL\Input]
final class InvalidIsPublicOnInput
{
    /**
     * @GQL\IsPublic("isAuthenticated()")
     */
    #[GQL\IsPublic('isAuthenticated()')]
    public string $field;
}
