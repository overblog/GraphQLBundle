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
     * @var string
     */
    public string $name;


    /**
     * @var string
     */
    public string $scalarType;
}
