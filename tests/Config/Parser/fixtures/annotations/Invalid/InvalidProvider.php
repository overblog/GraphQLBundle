<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Invalid;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider
 */
#[GQL\Provider]
final class InvalidProvider
{
    /**
     * @GQL\Query(type="Int", targetTypes="RootMutation2")
     */
    #[GQL\Query(type: 'Int', targetTypes: 'RootMutation2')]
    public function noQueryOnMutation(): array
    {
        return [];
    }

    /**
     * @GQL\Mutation(type="Int", targetTypes="RootQuery2")
     */
    #[GQL\Mutation(type: 'Int', targetTypes: 'RootQuery2')]
    public function noMutationOnQuery(): array
    {
        return [];
    }
}
