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
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class EnumValue extends Annotation implements NamedArgumentConstructorAnnotation
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

    public function __construct(?string $name = null, ?string $description = null, ?string $deprecationReason = null, ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->description = $description;
        $this->deprecationReason = $deprecationReason;
    }
}
