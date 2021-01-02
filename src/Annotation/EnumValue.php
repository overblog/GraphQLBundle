<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL enum value.
 *
 * @Annotation
 * @Target({"ANNOTATION", "CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
final class EnumValue implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * @var string
     */
    public ?string $name;

    /**
     * @var string
     */
    public ?string $description;

    /**
     * @var string
     */
    public ?string $deprecationReason;

    public function __construct(?string $name = null, ?string $description = null, ?string $deprecationReason = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->deprecationReason = $deprecationReason;
    }
}
