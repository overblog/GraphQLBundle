<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input\Planet;

/**
 * @GQL\Type
 */
class Battle
{
    /**
     * @GQL\Field(type="Planet", complexity="100 + childrenComplexity")
     */
    protected object $planet;

    /**
     * @GQL\Field(name="casualties", complexity="childrenComplexity * 5")
     */
    public function getCasualties(
        int $areaId,
        string $raceId,
        int $dayStart = null,
        int $dayEnd = null,
        string $nameStartingWith = '',
        Planet $planet = null,
        bool $away = false,
        float $maxDistance = null
    ): ?int {
        return 12;
    }
}
