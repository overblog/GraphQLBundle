<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\fixtures;

use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

final class CustomEdge extends Edge
{
    public string $customProperty;
}
