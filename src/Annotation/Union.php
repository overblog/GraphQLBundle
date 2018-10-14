<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Union
{
    /**
     * Union name.
     *
     * @var string
     */
    public $name;

    /**
     * Union types
     * 
     * @required
     * @var array<string>
     */
    public $types;
}
