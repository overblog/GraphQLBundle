<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL control.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
abstract class AbstractGraphQLControl
{
    /**
     * @var string
     */
    public $method;
}
