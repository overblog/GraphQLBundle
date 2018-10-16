<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Autoguess;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @ORM\Entity
 */
class Autoguess
{
    /**
     * @ORM\Column
     * @GQL\Field
     */
    private $field1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @GQL\Field
     */
    private $field2;

    /**
     * @ORM\OneToMany(targetEntity="Autoguess2")
     * @GQL\Field
     */
    private $field3;

    /**
     * @ORM\ManyToOne(targetEntity="Autoguess2")
     * @GQL\Field
     */
    private $field4;

    /**
     * @ORM\OneToOne(targetEntity="Autoguess2")
     * @GQL\Field
     */
    private $field5;

    /**
     * @ORM\ManyToMany(targetEntity="Autoguess2")
     * @GQL\Field
     */
    private $field6;

    /**
     * @GQL\Field
     * @ORM\OneToOne(targetEntity="Autoguess2")
     * @ORM\JoinColumn(nullable=true)
     */
    private $field7;
}
