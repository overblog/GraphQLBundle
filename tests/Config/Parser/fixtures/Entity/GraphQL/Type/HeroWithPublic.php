<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\IsPublic("isAuthenticated()")
 */
class HeroWithPublic
{
    /**
     * @GQL\Field(type="String!")
     */
    private $name;

    /**
     * @GQL\Field(type="Boolean!")
     * @GQL\IsPublic("hasRole('ROLE_ADMIN')")
     */
    private $secret;
}
