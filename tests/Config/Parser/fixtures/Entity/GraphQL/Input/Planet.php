<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\Entity\GraphQL\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\InputType
 * @GQL\Description("Planet Input type description")
 */
class Planet
{
    /**
     * @GQL\Field(type="String!")
     */
    private $name;

    /**
     * @GQL\Field(type="Int!")
     */
    private $population;
}
