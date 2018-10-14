<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\Description("RootQuery type")
 */
class RootQuery
{
    /**
     * @GQL\Field(type="Character", resolve="@=resolver('App\\MyResolver::getHero')")
     */
    private $hero;

    /**
     * @GQL\Field(type="Droid", resolve="@=resolver('App\\MyResolver::getDroid')")
     */
    private $droid;
}
