<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 * @GQL\Description("Planet Input type description")
 */
#[GQL\Input]
#[GQL\Description('Planet Input type description')]
class Planet
{
    /**
     * @GQL\Field(resolve="...")
     */
    #[GQL\Field(resolve: '...')]
    protected string $skipField;

    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: 'String!')]
    protected string $name;

    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: 'Int!')]
    protected string $population;

    /**
     * @GQL\Field
     */
    #[GQL\Field]
    protected string $description;

    /**
     * @GQL\Field
     * @ORM\Column(type="integer", nullable=true)
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $diameter;

    /**
     * @GQL\Field
     * @ORM\Column(type="boolean")
     */
    #[GQL\Field]
    protected int $variable;

    // @phpstan-ignore-next-line
    protected $dummy;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     */
    #[GQL\Field]
    protected array $tags;
}
