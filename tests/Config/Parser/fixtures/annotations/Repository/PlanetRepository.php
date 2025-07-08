<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Repository;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type\Planet;

/**
 * @GQL\Provider(prefix="planet_")
 *
 * @GQL\Access("default_access")
 *
 * @GQL\IsPublic("default_public")
 */
#[GQL\Provider(prefix: 'planet_')]
#[GQL\Access('default_access')]
#[GQL\IsPublic('default_public')]
final class PlanetRepository
{
    /**
     * @GQL\Query(type="[Planet]")
     *
     * @GQL\Arg(type="String!", name="keyword")
     */
    #[GQL\Query(type: '[Planet]')]
    #[GQL\Arg(type: 'String!', name: 'keyword')]
    public function searchPlanet(string $keyword): array
    {
        return [];
    }

    /**
     * @GQL\Query(type="[Planet]")
     *
     * @GQL\Arg(type="Int!", name="distance")
     */
    #[GQL\Query(type: '[Planet]')]
    #[GQL\Arg(type: 'Int!', name: 'distance')]
    public function searchStar(int $distance): array
    {
        return [];
    }

    /**
     * @GQL\Mutation(type="Planet")
     *
     * @GQL\Arg(type="PlanetInput!", name="planetInput")
     *
     * @GQL\IsPublic("override_public")
     */
    #[GQL\Mutation(type: 'Planet')]
    #[GQL\Arg(type: 'PlanetInput!', name: 'planetInput')]
    #[GQL\IsPublic('override_public')]
    public function createPlanet(array $planetInput): array
    {
        return [];
    }

    /**
     * @GQL\Query(type="[Planet]", targetTypes="Droid", name="allowedPlanets")
     *
     * @GQL\Access("override_access")
     */
    #[GQL\Query(type: '[Planet]', targetTypes: 'Droid', name: 'allowedPlanets')]
    #[GQL\Access('override_access')]
    public function getAllowedPlanetsForDroids(): array
    {
        return [];
    }

    /**
     * @GQL\Query(type="Planet", targetTypes="RootQuery2")
     */
    #[GQL\Query(type: 'Planet', targetTypes: 'RootQuery2')]
    public function getPlanetSchema2(): ?Planet
    {
        return null;
    }

    /**
     * @GQL\Mutation(type="Planet", targetTypes="RootMutation2")
     *
     * @GQL\Arg(type="PlanetInput!", name="planetInput")
     *
     * @GQL\IsPublic("override_public")
     */
    #[GQL\Mutation(type: 'Planet', targetTypes: 'RootMutation2')]
    #[GQL\Arg(type: 'PlanetInput!', name: 'planetInput')]
    #[GQL\IsPublic('override_public')]
    public function createPlanetSchema2(array $planetInput): array
    {
        return [];
    }

    /**
     * @GQL\Mutation(targetTypes={"RootMutation", "RootMutation2"})
     */
    #[GQL\Mutation(targetTypes: ['RootMutation', 'RootMutation2'])]
    public function destroyPlanet(int $planetId): bool
    {
        return true;
    }

    /**
     * @GQL\Query(targetTypes={"RootQuery", "RootQuery2"})
     */
    #[GQL\Query(targetTypes: ['RootQuery', 'RootQuery2'])]
    public function isPlanetDestroyed(int $planetId): bool
    {
        return true;
    }

    /**
     * @GQL\Query(targetTypes={"Droid", "Mandalorian"}, name="armorResistance")
     */
    #[GQL\Query(name: 'armorResistance', targetTypes: ['Droid', 'Mandalorian'])]
    public function getArmorResistance(): int
    {
        return 10;
    }

    /**
     * @GQL\Query(type="Json")
     *
     * @GQL\Arg(name="maxDistance", type="Int!")
     * @GQL\Arg(name="planetId", type="Int!")
     */
    #[GQL\Query(type: 'Json')]
    #[GQL\Arg(name: 'maxDistance', type: 'Int!')]
    #[GQL\Arg(name: 'planetId', type: 'Int!')]
    public function getNextPlanet(int $planetId, int $minDistance, int $maxDistance): array
    {
        return [
            'planetId' => $planetId,
            'minDistance' => $minDistance,
            'maxDistance' => $maxDistance,
        ];
    }

    /**
     * @GQL\Query
     */
    #[GQL\Query]
    public function isHabitablePlanet(int $planetId, ResolveInfo $info): bool
    {
        return true;
    }
}
