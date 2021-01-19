<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL access on fields.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Access extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Field access.
     */
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
