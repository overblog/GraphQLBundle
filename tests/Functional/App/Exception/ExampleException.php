<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Exception;

class ExampleException
{
    public function __invoke(): void
    {
        throw new \InvalidArgumentException('Invalid argument exception', 321);
    }
}
