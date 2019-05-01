<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation\Relay;

use Overblog\GraphQLBundle\Annotation\Type;

/**
 * Annotation for GraphQL relay connection.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Connection extends Type
{
    /**
     * Connection Edge type.
     *
     * @var string
     */
    public $edge;

    /**
     * Connection Node type.
     *
     * @var string
     */
    public $node;
}
