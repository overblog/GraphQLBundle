<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL interface.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TypeInterface implements NamedArgumentConstructorAnnotation, Annotation
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

    public function __construct(?string $name = null, string $resolveType)
    {
        $this->name = $name;
        $this->resolveType = $resolveType;
    }
}
