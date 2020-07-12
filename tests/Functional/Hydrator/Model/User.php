<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model;

use Overblog\GraphQLBundle\Hydrator\Annotation as Hydrator;
use Overblog\GraphQLBundle\Hydrator\Converters as Convert;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Post;

class User
{
    /**
     * @Hydrator\Field("username")
     */
    public string $nickname;

    public string $firstName;

    public string $lastName;

    public ?Address $address = null;

    public array $friends = [];

    /**
     * @Hydrator\Field("postId")
     * @Convert\Entity("Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model\Post")
     */
    public Post $post;

    /**
     * @Hydrator\Field("birthdate")
     */
    public Birthdate $birth;
}
