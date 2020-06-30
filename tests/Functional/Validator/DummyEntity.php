<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DummyEntity.
 *
 * @Assert\Callback({"Overblog\GraphQLBundle\Tests\Functional\Validator\StaticValidator", "validateClass"})
 */
class DummyEntity
{
    /**
     * @Assert\EqualTo("Lorem Ipsum")
     */
    private string $string1;

    /**
     * @Assert\EqualTo("Lorem Ipsum")
     */
    private string $string2;

    /**
     * @Assert\EqualTo("{""text"":""Lorem Ipsum""}")
     */
    private string $string3;

    /**
     * @Assert\EqualTo("Dolor Sit Amet")
     */
    public function getString1(): string
    {
        return $this->string1;
    }

    /**
     * @Assert\EqualTo("Dolor Sit Amet")
     */
    public function getString2(): string
    {
        return $this->string2;
    }

    /**
     * @Assert\Json()
     */
    public function getString3(): string
    {
        return $this->string3;
    }
}
