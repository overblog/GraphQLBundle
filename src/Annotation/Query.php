<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL query.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Query extends Field
{
    /**
     * @var array<string>
     *
     * @deprecated This property is deprecated since 1.0 and will be removed in 1.1. Use $targetTypes instead.
     */
    public array $targetType;

    /**
     * The target types to attach this query to.
     *
     * @var array<string>
     */
    public array $targetTypes;
}
