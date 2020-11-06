<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL argument.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class Arg implements Annotation
{
    /**
     * Argument name.
     *
     * @Required
     *
     * @var string
     */
    public string $name;

    /**
     * Argument description.
     *
     * @var string
     */
    public string $description;

    /**
     * Argument type.
     *
     * @Required
     *
     * @var string
     */
    public string $type;

    /**
     * Default argument value.
     *
     * @var mixed
     */
    public $default;
}
