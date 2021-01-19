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
     * Interface name.
     */
    public ?string $name;

    /**
     * Resolver type for interface.
     */
    public string $resolveType;

    /**
     * @param string|null $name        The GraphQL name of the interface
     * @param string      $resolveType The express resolve type
     */
    public function __construct(string $name = null, string $resolveType)
    {
        $this->name = $name;
        $this->resolveType = $resolveType;
    }
}
