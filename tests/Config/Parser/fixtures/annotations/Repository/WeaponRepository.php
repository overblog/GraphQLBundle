<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Repository;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider(targetTypeQuery={"RootQuery2"}, targetTypeMutation="RootMutation2")
 */
class WeaponRepository
{
    /**
     * @GQL\Query
     */
    public function hasSecretWeapons(): bool
    {
        return true;
    }

    /**
     * @GQL\Query(targetType="RootQuery")
     */
    public function countSecretWeapons(): int
    {
        return 2;
    }

    /**
     * @GQL\Mutation
     */
    public function createLightsaber(): bool
    {
        return true;
    }
}
