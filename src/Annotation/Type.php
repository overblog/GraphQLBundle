<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("CLASS")
 */
class Type implements Annotation
{
    /**
     * Type name.
     *
     * @var string
     */
    public $name;

    /**
     * Type inherited interfaces.
     *
     * @var string[]
     */
    public $interfaces;

    /**
     * Is the type a relay payload.
     *
     * @var bool
     */
    public $isRelay = false;

    /**
     * Expression to a target fields resolver.
     *
     * @var string
     */
    public $resolveField;

    /**
     * List of fields builder.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\FieldsBuilder>
     */
    public $builders = [];
}
