<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates;

/**
 * @GQL\Type
 * @GQL\Description("The Planet type")
 */
#[GQL\Type]
#[GQL\Description("The Planet type")]
class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: "String!")]
    protected string $name;

    /**
     * @GQL\Field(type="GalaxyCoordinates")
     */
    #[GQL\Field(type: "GalaxyCoordinates")]
    protected GalaxyCoordinates $location;

    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: "Int!")]
    protected int $population;

    /**
     * @GQL\Field
     * @GQL\FieldBuilder(value="NoteFieldBuilder", config={"option1"="value1"})
     */
    #[GQL\Field]
    #[GQL\FieldBuilder("NoteFieldBuilder", ["option1" => "value1"])]
    public array $notes;

    /**
     * @GQL\Field(
     *   type="Planet",
     *   resolve="@=resolver('closest_planet', [args['filter']])"
     * )
     * @GQL\ArgsBuilder(value="PlanetFilterArgBuilder", config={"option2"="value2"})
     */
    #[GQL\Field(type: "Planet", resolve: "@=resolver('closest_planet', [args['filter']])")]
    #[GQL\ArgsBuilder("PlanetFilterArgBuilder", ["option2" => "value2"])]
    public Planet $closestPlanet;

    /**
     * @GQL\Field(fieldBuilder={"NoteFieldBuilder", {"option1": "value1"}})
     */
    #[GQL\Field(fieldBuilder: ["NoteFieldBuilder", ["option1" => "value1"]])]
    public array $notesDeprecated;

    /**
     * @GQL\Field(
     *   type="Planet",
     *   argsBuilder={"PlanetFilterArgBuilder", {"option2": "value2"}},
     *   resolve="@=resolver('closest_planet', [args['filter']])"
     * )
     */
    #[GQL\Field(type: "Planet", argsBuilder: ["PlanetFilterArgBuilder", ["option2" => "value2"]], resolve: "@=resolver('closest_planet', [args['filter']])")]
    public Planet $closestPlanetDeprecated;
}
