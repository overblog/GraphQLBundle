<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQLBundle\Hydrator\Annotation as Hydrator;
use Overblog\GraphQLBundle\Hydrator\Converters as Convert;

/**
 * @ORM\Entity
 * @Hydrator\Model(identifier="")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column
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
     * @ORM\Column
     */
    public ?Address $address = null;

    /**
     * @ORM\Column
     */
    public array $friends = [];

    /**
     * @ORM\OneToMany(targetEntity="Post")
     * @Hydrator\Field("postId")
     * @var Post[]
     */
    public array $posts;

    /**
     * @ORM\Column
     * @Hydrator\Field("birthdate")
     */
    public Birthdate $birth;

    public function __construct(
        ?int $id,
        string $nickname,
        string $firstName,
        string $lastName,
        ?Address $address,
        array $friends,
        array $posts,
        Birthdate $birth
    ) {
        $this->id = $id;
        $this->nickname = $nickname;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->address = $address;
        $this->friends = $friends;
        $this->posts = $posts;
        $this->birth = $birth;
    }
}
