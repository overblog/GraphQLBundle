<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Interfaces;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\TypeInterface(resolveType="@=resolver('character_type', [value])")
 * @GQL\Description("The character interface")
 */
class Character
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Description("The id of the character")
     */
    private $id;

    /**
     * @GQL\Field(type="String!")
     * @GQL\Description("The name of the character")
     */
    private $name;
}