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
     * The target types to attach this mutation to (useful when multiple schemas are allowed).
     *
     * @var array<string>
     */
    public array $targetType;
}
