<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\fixtures;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;

class CustomConnection extends Connection
{
    public int $averageAge;
}
