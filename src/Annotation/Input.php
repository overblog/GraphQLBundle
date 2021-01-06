<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL input type.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Input extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Type name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * Is the type a relay input.
     * 
     * @var boolean
     */
    public bool $isRelay = false;

    public function __construct(?string $name = null, bool $isRelay = false, ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->isRelay = $isRelay;
    }
}
