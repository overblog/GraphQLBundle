<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Hydrator\Annotation as Hydrator;
use Overblog\GraphQLBundle\Hydrator\Converters as Convert;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public ?int $id = null;

    /**
     * @ORM\Column
     * @Hydrator\Field(mapFrom="username")
     */
    public string $nickname;

    /**
     * @ORM\Column
     */
    public string $firstName;

    /**
     * @ORM\Column
     */
    public string $lastName;

    /**
     * @ORM\OneToOne(targetEntity="Address", cascade={"PERSIST"})
     */
    public ?Address $address = null;

    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="friends")
     */
    public iterable $friends = [];

    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="user", cascade={"PERSIST"})
     * @Hydrator\Field("postId")
     * @var Post[]
     */
    public iterable $posts;

    /**
     * @ORM\OneToOne(targetEntity="Birthdate", cascade={"PERSIST"})
     * @Hydrator\Field("birthdate")
     */
    public Birthdate $birth;
}
