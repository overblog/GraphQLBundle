<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Union implements Annotation
{
    /**
     * Union name.
     *
     * @var string
     */
    public string $name;

    /**
     * Union types.
     *
     * @var array<string>
     */
    public array $types;

    /**
     * Resolver type for union.
     *
     * @var string
     */
    public string $resolveType;
}
