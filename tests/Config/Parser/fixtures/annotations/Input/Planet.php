<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

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
    protected $name;

    /**
     * @GQL\Field(type="Int!")
     */
    protected $population;
}
