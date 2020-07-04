<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model;

use Overblog\GraphQLBundle\Hydrator\Annotation as Hydrator;
use Overblog\GraphQLBundle\Hydrator\Converters as Convert;



class User extends AbstractUser
{
    /**
     * @Hydrator\Field(
     *     name="username"
     *     converters={}
     * )
     */
    public string $nickname;
    public string $firstName;
    public string $lastName;
    public ?Address $address = null;
    public array $friends = [];

    /**
     * @Hydrator\Field(name="birthdate")
     */
    public Birthdate $birth;
}
