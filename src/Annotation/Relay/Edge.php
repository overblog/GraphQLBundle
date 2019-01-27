<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation\Relay;

use Overblog\GraphQLBundle\Annotation\Type;

/**
 * Annotation for GraphQL connection edge.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Edge extends Type
{
    /**
     * Edge Node type.
     *
     * @required
     *
     * @var string
     */
    public $node;
}
