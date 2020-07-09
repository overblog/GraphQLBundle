<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class InvalidArgumentGuessing
{
    /**
     * @GQL\Field(name="guessFailed")
     *
     * @param mixed $test
     */
    public function guessFail($test): int
    {
        return 12;
    }
}
