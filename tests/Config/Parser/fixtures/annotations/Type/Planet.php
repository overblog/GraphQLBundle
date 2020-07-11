<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates;

/**
 * @GQL\Type
 * @GQL\Description("The Planet type")
 */
class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $name;

    /**
     * @GQL\Field(type="GalaxyCoordinates")
     */
    protected GalaxyCoordinates $location;

    /**
     * @GQL\Field(type="Int!")
     */
    protected int $population;

    /**
     * @GQL\Field(fieldBuilder={"NoteFieldBuilder", {"option1": "value1"}})
     */
    public array $notes;

    /**
     * @GQL\Field(
     *   type="Planet",
     *   argsBuilder={"PlanetFilterArgBuilder", {"option2": "value2"}},
     *   resolve="@=resolver('closest_planet', [args['filter']])"
     * )
     */
    public Planet $closestPlanet;
}
