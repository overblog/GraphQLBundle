<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Enum implements Annotation
{
    /**
     * Enum name.
     *
     * @var string
     */
    public $name;

    /**
     * @var array<\Overblog\GraphQLBundle\Annotation\EnumValue>
     */
    public $values;
}
