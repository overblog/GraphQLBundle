<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL scalar.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Scalar implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * @var string
     */
    public ?string $name;

    /**
     * @var string
     */
    public ?string $scalarType;
    
    public function __construct(?string $name = null, ?string $scalarType = null)
    {
        $this->name = $name;
        $this->scalarType = $scalarType;
    }
}
