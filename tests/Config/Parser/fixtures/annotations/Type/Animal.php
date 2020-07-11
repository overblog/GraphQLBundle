<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type()
 * @GQL\Description("The character interface")
 */
abstract class Animal
{
    /**
     * @GQL\Field(type="String!")
     * @GQL\Description("The name of the animal")
     */
    private string $name;

    /**
     * @GQL\Field(type="String!")
     */
    private string $lives;
}
