<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DummyEntity.
 *
 *
 * @Assert\Callback({"Overblog\GraphQLBundle\Tests\Functional\Validator\StaticValidator", "validateClass"})
 */
class DummyEntity
{
    /**
     * @Assert\EqualTo("Lorem Ipsum")
     */
    private $string1;

    /**
     * @Assert\EqualTo("Lorem Ipsum")
     */
    private $string2;

    /**
     * @Assert\EqualTo("{""text"":""Lorem Ipsum""}")
     */
    private $string3;

    /**
     * @Assert\EqualTo("Dolor Sit Amet")
     */
    public function getString1()
    {
        return $this->string1;
    }

    /**
     * @Assert\EqualTo("Dolor Sit Amet")
     */
    public function getString2()
    {
        return $this->string2;
    }

    /**
     * @Assert\Json()
     */
    public function getString3()
    {
        return $this->string3;
    }
}
