<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Relay;

use Overblog\GraphQLBundle\Annotation as GQL;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

/**
 * @GQL\Relay\Edge(node="Character")
 */
class FriendsConnectionEdge extends Edge
{
}
