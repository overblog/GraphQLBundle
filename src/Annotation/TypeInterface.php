<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL interface.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TypeInterface extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Interface name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * Resolver type for interface.
     *
     * @Required
     * 
     * @var string
     */
    public string $resolveType;

    public function __construct(?string $name = null, string $resolveType, ?string $value = null)
    {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->resolveType = $resolveType;
    }
}
