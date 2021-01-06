<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Enum extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Enum name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * @var array<\Overblog\GraphQLBundle\Annotation\EnumValue>
     * 
     * @deprecated
     */
    public array $values;

    public function __construct(?string $name = null, array $values = [], ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->values = $values;
    }
}
