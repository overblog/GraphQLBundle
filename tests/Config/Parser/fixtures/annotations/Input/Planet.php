<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
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
    private string $skipField;

    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: 'String!')]
    private string $name;

    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: 'Int!')]
    private string $population;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    private string $description;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private ?int $diameter;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    private int $variable;

    // @phpstan-ignore-next-line
    private $dummy;

    /**
     * @GQL\Field(type="[String]!")
     */
    #[GQL\Field(type: '[String]!')]
    private array $tags;
}
