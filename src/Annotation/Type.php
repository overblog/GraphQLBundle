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
    public string $name;

    /**
     * Type inherited interfaces.
     *
     * @var string[]
     */
    public array $interfaces;

    /**
     * Is the type a relay payload.
     *
     * @var bool
     */
    public bool $isRelay = false;

    /**
     * Expression to a target fields resolver.
     *
     * @var string
     */
    public string $resolveField;

    /**
     * List of fields builder.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\FieldsBuilder>
     */
    public array $builders = [];

    /**
     * Expression to resolve type for interfaces.
     *
     * @var string
     */
    public string $isTypeOf;
}
