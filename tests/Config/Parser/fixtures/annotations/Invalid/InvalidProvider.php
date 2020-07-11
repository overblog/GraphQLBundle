<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider
 */
class InvalidProvider
{
    /**
     * @GQL\Query(type="Int", targetType="RootMutation2")
     */
    public function noQueryOnMutation(): array
    {
        return [];
    }

    /**
     * @GQL\Mutation(type="Int", targetType="RootQuery2")
     */
    public function noMutationOnQuery(): array
    {
        return [];
    }
}
