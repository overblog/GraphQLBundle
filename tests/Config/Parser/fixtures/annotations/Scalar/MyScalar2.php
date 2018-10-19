<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Scalar(name="MyScalar", scalarType="newObject('App\\Type\\EmailType')")
 */
class MyScalar2
{
}
