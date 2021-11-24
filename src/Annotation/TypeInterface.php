<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
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
     * Resolver type for interface.
     */
    public string $resolveType;

    /**
     * Interface name.
     */
    public ?string $name;

    /**
     * @param string      $resolveType The express resolve type
     * @param string|null $name        The GraphQL name of the interface
     */
    public function __construct(string $resolveType, string $name = null)
    {
        $this->resolveType = $resolveType;
        $this->name = $name;
    }
}
