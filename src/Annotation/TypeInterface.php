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
     * @param string      $resolveType The express resolve type
     */
    public function __construct(string $name = null, string $resolveType = null)
    {
        // TODO: 1.0: Remove optionality for both parameters.
        // Previously, only the name was optional, but resolveType was always required
        // But in PHP 8.1 you cannot define an optional parameter before a required one.
        // To not break BC we now also make the resolveType optional.
        $this->name = $name;
        $this->resolveType = $resolveType;
    }
}
