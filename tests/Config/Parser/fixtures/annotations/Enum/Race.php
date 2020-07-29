<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Enum;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Tests\Config\Parser\Constants;

/**
 * @GQL\Enum(values={
 *    @GQL\EnumValue(name="CHISS", description="The Chiss race"),
 *    @GQL\EnumValue(name="ZABRAK", deprecationReason="The Zabraks have been wiped out")
 * })
 * @GQL\Description("The list of races!")
 */
class Race
{
    public const HUMAIN = 1;
    public const CHISS = '2';
    public const ZABRAK = '3';
    public const TWILEK = Constants::TWILEK;

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
