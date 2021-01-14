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
class Lightsaber
{
    /**
     * @ORM\Column
     * @GQL\Field
     */
    #[GQL\Field]
    protected $color;

    /**
     * @ORM\Column(type="text")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $text;

    /**
     * @ORM\Column(type="string")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $string;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     * @GQL\Field
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $battles;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Hero")
     * @ORM\JoinColumn(nullable=true)
     */
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $currentHolder;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    #[GQL\Field]
    #[GQL\Deprecated("No more tags on lightsabers")]
    protected array $tags;

    /**
     * @ORM\Column(type="float")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $float;

    /**
     * @ORM\Column(type="decimal")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $decimal;

    /**
     * @ORM\Column(type="bool")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $bool;

    /**
     * @ORM\Column(type="boolean")
     * @GQL\Field
     */
    #[GQL\Field]
    protected $boolean;
}
