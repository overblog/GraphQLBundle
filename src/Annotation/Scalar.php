<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL scalar.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Scalar extends Annotation implements NamedArgumentConstructorAnnotation
{
    public ?string $name;

    public ?string $scalarType;

    /**
     * @param string|null $name       The GraphQL name of the Scalar
     * @param string|null $scalarType Expression to reuse an other scalar type
     */
    public function __construct(string $name = null, string $scalarType = null)
    {
        $this->name = $name;
        $this->scalarType = $scalarType;
    }
}
