<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @ORM\Entity
 */
class Lightsaber
{
    /**
     * @ORM\Column
     * @GQL\Field
     */
    protected string $color;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @GQL\Field
     */
    // @phpstan-ignore-next-line
    protected $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     * @GQL\Field
     */
    // @phpstan-ignore-next-line
    protected $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     * @GQL\Field
     */
    // @phpstan-ignore-next-line
    protected $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     * @GQL\Field
     */
    // @phpstan-ignore-next-line
    protected $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     * @GQL\Field
     */
    // @phpstan-ignore-next-line
    protected $battles;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Hero")
     * @ORM\JoinColumn(nullable=true)
     */
    // @phpstan-ignore-next-line
    protected $currentHolder;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    protected array $tags;
}
