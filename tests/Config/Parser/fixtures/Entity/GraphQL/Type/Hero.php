<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\Description("The Hero type")
 */
class Hero
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Deprecated("it is now deprecated")
     */
    private $name;

    /**
     * @GQL\Field(type="[Character]", resolve="@=resolver('App\\MyResolver::getFriends')")
     */
    private $friends;
}
