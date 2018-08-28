<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLEdgeFields
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
