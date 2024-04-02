<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
#[GQL\Type]
final class InvalidArgumentNaming
{
    /**
     * @GQL\Field(name="guessFailed")
     *
     * @GQL\Arg(name="missingParameter", type="String")
     */
    #[GQL\Field(name: 'guessFailed')]
    #[GQL\Arg(name: 'missingParameter', type: 'String')]
    public function guessFail(int $test): int
    {
        return 12;
    }
}
