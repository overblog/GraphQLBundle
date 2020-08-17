<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL description.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
final class Description implements Annotation
{
    /**
     * The object description.
     *
     * @Required
     *
     * @var string
     */
    public string $value;
}
