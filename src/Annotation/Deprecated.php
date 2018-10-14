<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL to mark a field as deprecated
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class Deprecated
{
    /**
     * The deprecation reason.
     *
     * @var string
     */
    public $value;
}
