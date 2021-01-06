<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Union extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Union name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * Union types.
     *
     * @var array<string>
     */
    public array $types = [];

    /**
     * Resolver type for union.
     * 
     * @var string
     */
    public ?string $resolveType;
    
    public function __construct(?string $name = null, array $types = [], ?string $resolveType = null, ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->types = $types;
        $this->resolveType = $resolveType;
    }
}
