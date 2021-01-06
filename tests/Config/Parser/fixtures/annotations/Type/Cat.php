<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\Description("The Cat type")
 */
#[GQL\Type]
#[GQL\Description("The Cat type")]
class Cat extends Animal
{
    /**
     * @GQL\Field(type="Int!")
     */
    #[GQL\Field(type: "Int!")]
    protected int $lives;

    /**
     * @GQL\Field
     *
     * @var string[]
     */
    #[GQL\Field]
    protected array $toys;

    /**
     * @GQL\Field("shortcut")
     */
    #[GQL\Field("shortcut")]
    protected ?string $field;
}
