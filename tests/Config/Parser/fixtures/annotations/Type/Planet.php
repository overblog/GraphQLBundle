<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\Description("The Planet type")
 */
class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    protected $name;

    /**
     * @GQL\Field(type="GalaxyCoordinates")
     */
    protected $location;

    /**
     * @GQL\Field(type="Int!")
     */
    protected $population;

    /**
     * @GQL\Field(fieldBuilder={"NoteFieldBuilder", {"option1": "value1"}})
     */
    public $notes;

    /**
     * @GQL\Field(
     *   type="Planet",
     *   argsBuilder={"PlanetFilterArgBuilder", {"option2": "value2"}},
     *   resolve="@=resolver('closest_planet', [args['filter']])"
     * )
     */
    public $closestPlanet;
}
