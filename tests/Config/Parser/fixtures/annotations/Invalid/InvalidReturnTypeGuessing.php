<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class InvalidReturnTypeGuessing
{
    /**
     * @GQL\Field(name="guessFailed")
     * @phpstan-ignore-next-line
     */
    public function guessFail(int $test)
    {
        return 12;
    }
}
