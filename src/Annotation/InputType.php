<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL input type.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class InputType
{
    /**
     * Type name.
     *
     * @var string
     */
    public $name;

    /**
     * Is the type a relay input
     * 
     * @var boolean
     */
    public $isRelay = false;
}
