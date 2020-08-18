<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 * @GQL\Description("Planet Input type description")
 */
class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $name;

    /**
     * @GQL\Field(type="Int!")
     */
    protected string $population;

    /**
     * @GQL\Field
     */
    protected string $description;

    /**
     * @GQL\Field
     * @ORM\Column(type="integer", nullable=true)
     */
    // @phpstan-ignore-next-line
    protected $diameter;

    /**
     * @GQL\Field
     * @ORM\Column(type="boolean")
     */
    protected int $variable;

    // @phpstan-ignore-next-line
    protected $dummy;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     */
    protected array $tags;
}
