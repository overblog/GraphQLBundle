<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL fields builders.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class FieldsBuilder implements Annotation
{
    /**
     * Builder name.
     *
     * @Required
     *
     * @var string
     */
    public string $builder;

    /**
     * The builder config.
     *
     * @var mixed
     */
    public $builderConfig = [];
}
