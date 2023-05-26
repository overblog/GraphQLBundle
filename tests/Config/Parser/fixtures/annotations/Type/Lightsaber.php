<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 *
 * @ORM\Entity
 */
#[GQL\Type]
final class Lightsaber
{
    /**
     * @ORM\Column
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $color;

    /**
     * @ORM\Column(type="text")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $text;

    /**
     * @ORM\Column(type="string")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $string;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $battles;

    /**
     * @GQL\Field
     *
     * @ORM\OneToOne(targetEntity="Hero")
     *
     * @ORM\JoinColumn(nullable=true)
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $currentHolder;

    /**
     * @GQL\Field
     *
     * @ORM\Column(type="text[]")
     *
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    #[GQL\Field]
    #[GQL\Deprecated('No more tags on lightsabers')]
    public array $tags;

    /**
     * @ORM\Column(type="float")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $float;

    /**
     * @ORM\Column(type="decimal")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $decimal;

    /**
     * @ORM\Column(type="bool")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $bool;

    /**
     * @ORM\Column(type="boolean")
     *
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    public $boolean;
}
