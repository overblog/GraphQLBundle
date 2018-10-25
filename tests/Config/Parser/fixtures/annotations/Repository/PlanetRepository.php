<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Repository;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Provider(prefix="planet_")
 */
class PlanetRepository
{
    /**
     * @GQL\Query(type="[Planet]", args={
     *    @GQL\Arg(type="String!", name="keyword")
     * })
     */
    public function searchPlanet(string $keyword)
    {
        return [];
    }

    /**
     * @GQL\Mutation(type="Planet", args={
     *    @GQL\Arg(type="PlanetInput!", name="planetInput")
     * })
     */
    public function createPlanet(array $planetInput)
    {
        return [];
    }

    /**
     * @GQL\Query(type="[Planet]", targetType="Droid", name="allowedPlanets")
     */
    public function getAllowedPlanetsForDroids()
    {
        return [];
    }
}
