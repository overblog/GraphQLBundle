<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class GraphQLMutation
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $args;

    /**
     * @var array
     */
    public $input;

    /**
     * @var string
     */
    public $payload;
}
