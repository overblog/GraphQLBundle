<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 *
 * @GQL\Description("Planet Input type description")
 */
#[GQL\Input]
#[GQL\Description('Planet Input type description')]
final class Planet
{
    /**
     * @GQL\Field(resolve="...")
     */
    #[GQL\Field(resolve: '...')]
    public string $skipField;

    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: 'String!')]
    public string $name;

    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: 'Int!')]
    public string $population;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    public string $description;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public ?int $diameter;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    public int $variable;

    // @phpstan-ignore-next-line
    public $dummy;

    /**
     * @GQL\Field(type="[String]!")
     */
    #[GQL\Field(type: '[String]!')]
    public array $tags;
}
