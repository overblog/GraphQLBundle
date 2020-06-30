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
    protected int $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     * @GQL\Field
     */
    protected Hero $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     * @GQL\Field
     */
    protected Hero $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     * @GQL\Field
     */
    protected Crystal $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     * @GQL\Field
     */
    protected Battle $battles;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Hero")
     * @ORM\JoinColumn(nullable=true)
     */
    protected Hero $currentHolder;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    protected array $tags;
}
