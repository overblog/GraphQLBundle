<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL public on fields.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class IsPublic extends Annotation
{
    /**
     * Field publicity.
     */
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
