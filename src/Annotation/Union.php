<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL union.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Union implements NamedArgumentConstructorAnnotation, Annotation
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
    
    public function __construct(?string $name = null, array $types = [], ?string $resolveType = null)
    {
        $this->name = $name;
        $this->types = $types;
        $this->resolveType = $resolveType;
    }
}
