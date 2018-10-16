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
    public $name;

    /**
     * Resolver type for interface.
     *
     * @var string
     */
    public $resolveType;
}
