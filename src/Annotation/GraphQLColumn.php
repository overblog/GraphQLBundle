<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLColumn
{
    /**
     * Type.
     *
     * @var string
     */
    public $type;

    /**
     * Is nullable?
     *
     * @var bool
     */
    public $nullable;
}
