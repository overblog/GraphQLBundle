<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input\Planet;

/**
 * @GQL\Type
 */
#[GQL\Type]
final class Battle
{
    /**
     * @GQL\Field(type="Planet", complexity="100 + childrenComplexity")
     */
    #[GQL\Field(type: 'Planet', complexity: '100 + childrenComplexity')]
    public object $planet;

    /**
     * @GQL\Field(name="casualties", complexity="childrenComplexity * 5")
     *
     * @GQL\Arg(name="raceId", type="String!", description="A race ID")
     * @GQL\Arg(name="cases", type="[String!]!")
     */
    #[GQL\Field(name: 'casualties', complexity: 'childrenComplexity * 5')]
    #[GQL\Arg(name: 'raceId', type: 'String!', description: 'A race ID')]
    #[GQL\Arg(name: 'cases', type: '[String!]!')]
    public function getCasualties(
        int $areaId,
        ?string $raceId,
        int $dayStart = null,
        int $dayEnd = null,
        string $nameStartingWith = '',
        Planet $planet = null,
        bool $away = false,
        float $maxDistance = null,
        array $cases = []
    ): ?int {
        return 12;
    }
}
