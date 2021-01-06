<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum("Pets")
 * @GQL\EnumValue("DOGS")
 * @GQL\EnumValue("CATS")
 */
#[GQL\Enum("Pets")]
#[GQL\EnumValue("DOGS")]
#[GQL\EnumValue("CATS")]
class Pet
{
    public const DOGS = 'dog';
    public const CATS = 'cat';

    /**
     * @var int|string
     */
    public $value;

    /**
     * @param int|string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
