<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Type extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Type name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * Type inherited interfaces.
     *
     * @var string[]
     */
    public array $interfaces = [];

    /**
     * Is the type a relay payload.
     * 
     * @var boolean
     */
    public bool $isRelay = false;

    /**
     * Expression to a target fields resolver.
     * 
     * @var string
     */
    public ?string $resolveField;

    /**
     * List of fields builder.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\FieldsBuilder>
     * 
     * @deprecated
     */
    public array $builders = [];

    /**
     * Expression to resolve type for interfaces.
     * 
     * @var string
     */
    public ?string $isTypeOf;

    public function __construct(
        ?string $name = null,
        array $interfaces = [],
        bool $isRelay = false,
        ?string $resolveField = null,
        array $builders = [],
        ?string $isTypeOf = null,
        ?string $value = null
    ) {
        if ($name && $value) {
            $this->cumulatedAttributesException('name', $value, $name);
        }
        $this->name = $value ?: $name;
        $this->interfaces = $interfaces;
        $this->isRelay = $isRelay;
        $this->resolveField = $resolveField;
        $this->builders = $builders;
        $this->isTypeOf = $isTypeOf;
    }
}
