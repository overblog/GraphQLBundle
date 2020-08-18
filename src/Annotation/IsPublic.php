<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL public on fields.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
final class IsPublic implements Annotation
{
    /**
     * Field publicity.
     *
     * @Required
     *
     * @var string
     */
    public string $value;
}
