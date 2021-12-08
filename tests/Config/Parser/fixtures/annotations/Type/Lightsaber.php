<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @ORM\Entity
 */
#[GQL\Type]
final class Lightsaber
{
    /**
     * @ORM\Column
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $color;

    /**
     * @ORM\Column(type="text")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $text;

    /**
     * @ORM\Column(type="string")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $string;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $battles;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Hero")
     * @ORM\JoinColumn(nullable=true)
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $currentHolder;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    #[GQL\Field]
    #[GQL\Deprecated('No more tags on lightsabers')]
    private array $tags;

    /**
     * @ORM\Column(type="float")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $float;

    /**
     * @ORM\Column(type="decimal")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $decimal;

    /**
     * @ORM\Column(type="bool")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $bool;

    /**
     * @ORM\Column(type="boolean")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    private $boolean;
}
