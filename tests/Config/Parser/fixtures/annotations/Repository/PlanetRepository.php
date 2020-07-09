<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Repository;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider(prefix="planet_")
 * @GQL\Access("default_access")
 * @GQL\IsPublic("default_public")
 */
class PlanetRepository
{
    /**
     * @GQL\Query(type="[Planet]", args={
     *    @GQL\Arg(type="String!", name="keyword")
     * })
     */
    public function searchPlanet(string $keyword): array
    {
        return [];
    }

    /**
     * @GQL\Mutation(type="Planet", args={
     *    @GQL\Arg(type="PlanetInput!", name="planetInput")
     * })
     * @GQL\IsPublic("override_public")
     */
    public function createPlanet(array $planetInput): array
    {
        return [];
    }

    /**
     * @GQL\Query(type="[Planet]", targetType="Droid", name="allowedPlanets")
     * @GQL\Access("override_access")
     */
    public function getAllowedPlanetsForDroids(): array
    {
        return [];
    }
}
