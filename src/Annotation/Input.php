<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL input type.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Input implements Annotation
{
    /**
     * Type name.
     *
     * @var string
     */
    public $name;

    /**
     * Is the type a relay input.
     *
     * @var bool
     */
    public $isRelay = false;
}
