<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL access control.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class GraphQLResolver
{
    /**
     * @var string
     */
    public $resolve;
}
