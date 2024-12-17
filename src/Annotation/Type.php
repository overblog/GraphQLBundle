<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL type.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Type extends Annotation
{
    /**
     * Type name.
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
     */
    public bool $isRelay = false;

    /**
     * Expression to a target fields resolver.
     */
    public ?string $resolveField;

    /**
     * Expression to resolve type for interfaces.
     */
    public ?string $isTypeOf;

    /**
     * @param string|null          $name         The GraphQL name of the type
     * @param string[]             $interfaces   List of GraphQL interfaces implemented by the type
     * @param bool                 $isRelay      Set to true to make the type compatible with relay
     * @param string|null          $resolveField An expression to resolve the field value
     * @param string|null          $isTypeOf     An expression to resolve if the field is of given type
     */
    public function __construct(
        ?string $name = null,
        array $interfaces = [],
        bool $isRelay = false,
        ?string $resolveField = null,
        ?string $isTypeOf = null
    ) {
        $this->name = $name;
        $this->interfaces = $interfaces;
        $this->isRelay = $isRelay;
        $this->resolveField = $resolveField;
        $this->isTypeOf = $isTypeOf;
    }
}
