<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL interface.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class TypeInterface implements Annotation
{
    /**
     * Interface name.
     *
     * @var string
     */
    public string $name;

    /**
     * Resolver type for interface.
     *
     * @Required
     *
     * @var string
     */
    public string $resolveType;
}
