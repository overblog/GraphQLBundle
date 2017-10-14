<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Exception;

class ExampleException
{
    public function __invoke()
    {
        throw new \InvalidArgumentException('Invalid argument exception');
    }
}
