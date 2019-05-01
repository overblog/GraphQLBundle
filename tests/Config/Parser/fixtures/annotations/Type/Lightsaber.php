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
    protected $color;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @GQL\Field
     */
    protected $size;

    /**
     * @ORM\OneToMany(targetEntity="Hero")
     * @GQL\Field
     */
    protected $holders;

    /**
     * @ORM\ManyToOne(targetEntity="Hero")
     * @GQL\Field
     */
    protected $creator;

    /**
     * @ORM\OneToOne(targetEntity="Crystal")
     * @GQL\Field
     */
    protected $crystal;

    /**
     * @ORM\ManyToMany(targetEntity="Battle")
     * @GQL\Field
     */
    protected $battles;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Hero")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $currentHolder;

    /**
     * @GQL\Field
     * @ORM\Column(type="text[]")
     * @GQL\Deprecated("No more tags on lightsabers")
     */
    protected $tags;
}
