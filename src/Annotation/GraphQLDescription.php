<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class GraphQLDescription
{
    /**
     * Type.
     *
     * @var string
     */
    public $description;
}
