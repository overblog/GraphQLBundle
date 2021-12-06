<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Union extends Annotation
{
    /**
     * Union name.
     */
    public ?string $name;

    /**
     * Union types.
     */
    public array $types = [];

    /**
     * Resolver type for union.
     */
    public ?string $resolveType;

    /**
     * @param string|null $name        The GraphQL name of the union
     * @param string[]    $types       List of types included in the union
     * @param string|null $resolveType The resolve type expression
     */
    public function __construct(string $name = null, array $types = [], ?string $resolveType = null)
    {
        $this->name = $name;
        $this->types = $types;
        $this->resolveType = $resolveType;
    }
}
