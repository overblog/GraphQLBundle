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
     * Interface name.
     */
    public ?string $name;

    /**
     * Resolver type for interface.
     */
    public string $resolveType;

    /**
     * @param string|null $name        The GraphQL name of the interface
     * @param string $resolveType The express resolve type
     */
    public function __construct(?string $name, string $resolveType)
    {
        $this->name = $name;
        $this->resolveType = $resolveType;
    }
}
