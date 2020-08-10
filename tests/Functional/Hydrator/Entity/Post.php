<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public int $id;

    /**
     * @ORM\Column
     */
    public string $title;

    /**
     * @ORM\Column
     */
    public string $text;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="posts")
     */
    public User $user;

    /**
     * @param array<int, int> $values
     */
    public function populateFromArray(array $values): self
    {
        [$title, $text] = $values;

        $this->title = $title;
        $this->text  = $text;

        return $this;
    }
}