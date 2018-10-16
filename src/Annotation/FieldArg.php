<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL field argument.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class FieldArg implements Annotation
{
    /**
     * Argument name.
     *
     * @required
     *
     * @var string
     */
    public $name;

    /**
     * Argument description.
     *
     * @var string
     */
    public $description;

    /**
     * Argument type.
     *
     * @required
     *
     * @var string
     */
    public $type;

    /**
     * Default argument value.
     *
     * @var mixed
     */
    public $default;
}
