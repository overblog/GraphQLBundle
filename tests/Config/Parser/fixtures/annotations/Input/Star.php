<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input("StarPlanet")
 */
#[GQL\Input("StarPlanet")]
class Star
{
    /**
     * @GQL\Field
     */
    #[GQL\Field]
    protected int $distance;
}
