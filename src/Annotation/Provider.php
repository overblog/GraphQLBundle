<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for operations provider.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Provider implements Annotation
{
    /**
     * Optionnal prefix for provider fields.
     * 
     * @var string
     */
    public string $prefix;

    /**
     * The default target types to attach the provider queries to.
     *
     * @var array<string>
     */
    public array $targetTypeQuery;

    /**
     * The default target types to attach the provider mutations to.
     *
     * @var array<string>
     */
    public array $targetTypeMutation;
}
