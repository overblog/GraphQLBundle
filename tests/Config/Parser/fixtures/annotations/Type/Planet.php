<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar\GalaxyCoordinates;

/**
 * @GQL\Type
 *
 * @GQL\Description("The Planet type")
 */
#[GQL\Type]
#[GQL\Description('The Planet type')]
final class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: 'String!')]
    public string $name;

    /**
     * @GQL\Field(type="GalaxyCoordinates")
     */
    #[GQL\Field(type: 'GalaxyCoordinates')]
    public GalaxyCoordinates $location;

    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: 'Int!')]
    public int $population;

    /**
     * @GQL\Field
     *
     * @GQL\FieldBuilder(name="NoteFieldBuilder", config={"option1"="value1"})
     */
    #[GQL\Field]
    #[GQL\FieldBuilder('NoteFieldBuilder', ['option1' => 'value1'])]
    public array $notes;

    /**
     * @GQL\Field(
     *   type="Planet",
     *   resolve="@=query('closest_planet', [args['filter']])"
     * )
     *
     * @GQL\ArgsBuilder(name="PlanetFilterArgBuilder", config={"option2"="value2"})
     */
    #[GQL\Field(type: 'Planet', resolve: "@=query('closest_planet', [args['filter']])")]
    #[GQL\ArgsBuilder('PlanetFilterArgBuilder', ['option2' => 'value2'])]
    public Planet $closestPlanet;
}
