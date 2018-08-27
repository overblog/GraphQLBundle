<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class GraphQLScalarType
{
    /**
     * Type.
     *
     * @var string
     */
    public $type;
}
