<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Overblog\GraphQLBundle\Tests\Functional\Hydrator\Repository\PostRepository")
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
     * @ORM\Column(type="string")
     */
    public string $title;

    /**
     * @ORM\Column(type="string")
     */
    public string $text;
}