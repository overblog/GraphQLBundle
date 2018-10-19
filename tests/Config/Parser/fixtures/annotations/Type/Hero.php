<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(interfaces={"Character"})
 * @GQL\Description("The Hero type")
 */
class Hero extends Character
{
    /**
     * @GQL\Field(type="Race")
     */
    protected $race;
}
