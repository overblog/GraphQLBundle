<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Address
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column
     */
    public string $street;

    /**
     * @ORM\Column
     */
    public string $city;

    /**
     * @ORM\Column(type="integer")
     */
    public int $zipCode;

    /**
     * @param array<int, int> $values
     */
    public function populateFromArray(array $values): self
    {
        [$street, $city, $zipCode] = $values;

        $this->street  = $street;
        $this->city    = $city;
        $this->zipCode = $zipCode;

        return $this;
    }
}
