<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Birthdate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     */
    public int $day;

    /**
     * @ORM\Column(type="integer")
     */
    public int $month;

    /**
     * @ORM\Column(type="integer")
     */
    public int $year;

    /**
     * @param array<int, int> $values
     */
    public function populateFromArray(array $values): self
    {
        [$day, $month, $year] = $values;

        $this->day   = $day;
        $this->month = $month;
        $this->year  = $year;

        return $this;
    }
}
