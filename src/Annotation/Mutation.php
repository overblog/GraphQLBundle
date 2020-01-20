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
     * The target type to attach this mutation to.
     *
     * @var string
     */
    public $targetType;
}
