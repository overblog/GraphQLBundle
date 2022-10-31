<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
final class InvalidPrivateMethod
{
    /**
     * @GQL\Field
     */
    #[GQL\Field]
    private function gql(): string
    {
        return 'invalid';
    }
}
