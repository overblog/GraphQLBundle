<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Scalar;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Scalar(doctrineType="datetime", phpType=\DateTime::class)
 */
class ScalarWithTypeMapping
{
}
