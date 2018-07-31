<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class GraphQLQuery
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
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $input;

    /**
     * @var string
     */
    public $argsBuilder;
}
