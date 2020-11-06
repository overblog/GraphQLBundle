<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL mutation.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Mutation extends Field
{
    /**
     * @var array<string>
     *
     * @deprecated This property is deprecated since 1.0 and will be removed in 1.1. Use $targetTypes instead.
     */
    public array $targetType;

    /**
     * The target types to attach this mutation to (useful when multiple schemas are allowed).
     *
     * @var array<string>
     */
    public array $targetTypes;
}
