<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation\Relay;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Overblog\GraphQLBundle\Annotation\Annotation;
use Overblog\GraphQLBundle\Annotation\Type;

/**
 * Annotation for GraphQL connection edge.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Edge extends Type
{
    /**
     * Edge Node type.
     */
    public string $node;

    public function __construct(string $node)
    {
        $this->node = $node;
    }
}
