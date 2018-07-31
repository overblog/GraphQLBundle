<?php

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type relation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
abstract class AbstractGraphQLRelation
{
    /**
     * @var string
     */
    public $target;

    /**
     * @var bool
     */
    public $nullable;
}
