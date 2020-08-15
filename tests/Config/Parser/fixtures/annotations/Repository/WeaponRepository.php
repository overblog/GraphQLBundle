<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Repository;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider
 * @GQL\Access("default_access", nullOnDenied=true)
 */
class WeaponRepository
{
    /**
     * @GQL\Query
     */
    public function searchSecretWeapon(): ?bool
    {
        return false;
    }
}
