<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\Access("isAuthenticated()")
 */
class HeroWithAccess
{
    /**
     * @GQL\Field(type="String!")
     */
    private $name;

    /**
     * @GQL\Field(type="Boolean!")
     * @GQL\Access("hasRole('ROLE_ADMIN')")
     */
    private $secret;
}
