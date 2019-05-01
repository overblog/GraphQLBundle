<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL scalar.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Scalar implements Annotation
{
    /**
     * Scalar name.
     *
     * @var string
     */
    public $name;

    /**
     * Scalar type.
     *
     * @var string
     */
    public $scalarType;
}
