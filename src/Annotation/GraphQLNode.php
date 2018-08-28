<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class GraphQLNode
{
    /**
     * Type.
     *
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $resolve;
}
