<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type()
 * @GQL\Description("The Cat type")
 */
class Cat extends Animal
{
    /**
     * @GQL\Field(type="Int!")
     */
    protected int $lives;
}
