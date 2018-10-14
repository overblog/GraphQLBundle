<?php

declare (strict_types = 1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Type
{
    /**
     * Type name.
     *
     * @var string
     */
    public $name;

    /**
     * Type inherited interfaces
     * 
     * @var string[]
     */
    public $interfaces;

    /**
     * Is the type a relay payload
     * 
     * @var boolean
     */
    public $isRelay = false;
}
