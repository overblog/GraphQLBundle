<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL operations (query or mutation).
 */
abstract class Operation implements Annotation
{
    /**
     * The operation name.
     */
    public $name;

    /**
     * Operation Type.
     *
     * @var string
     */
    public $type;

    /**
     * Operation arguments.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\Arg>
     */
    public $args;
}
