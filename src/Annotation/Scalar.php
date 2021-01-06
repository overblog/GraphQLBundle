<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL scalar.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Scalar extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * @var string
     */
    public ?string $name;

    /**
     * @var string
     */
    public ?string $scalarType;
    
    public function __construct(?string $name = null, ?string $scalarType = null, ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->scalarType = $scalarType;
    }
}
