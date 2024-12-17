<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL interface.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TypeInterface extends Annotation
{
    /**
     * Resolver type for interface.
     */
    public ?string $resolveType;

    /**
     * Interface name.
     */
    public ?string $name;

    /**
     * @param string|null   $resolveType The express resolve type
     * @param string|null   $name        The GraphQL name of the interface
     */
    public function __construct(?string $resolveType = null, ?string $name = null)
    {
        $this->resolveType = $resolveType;
        $this->name = $name;
    }
}
