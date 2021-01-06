<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type("Doggy")
 * @GQL\FieldsBuilder("MyFieldsBuilder")
 */
#[GQL\Type("Doggy")]
#[GQL\FieldsBuilder("MyFieldsBuilder")]
class Dog
{
    /**
     * @GQL\Field
     *
     * @var string[]
     */
    #[GQL\Field]
    protected array $toys;

    /**
     * @GQL\Field("catFights")
     * @GQL\ArgsBuilder("MyArgsBuilder")
     */
    #[GQL\Field("catFights")]
    #[GQL\ArgsBuilder("MyArgsBuilder")]
    public function getCountCatFights(): int
    {
        return 1000;
    }
}
