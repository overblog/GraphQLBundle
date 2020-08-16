<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL enum value.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class EnumValue implements Annotation
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string
     */
    public string $deprecationReason;
}
