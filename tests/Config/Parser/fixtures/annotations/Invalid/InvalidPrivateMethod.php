<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class InvalidPrivateMethod
{
    /**
     * @GQL\Field
     */
    protected function gql(): string
    {
        return 'invalid';
    }
}
