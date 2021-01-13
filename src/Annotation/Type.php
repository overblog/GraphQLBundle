<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
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
     * List of fields builder.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\FieldsBuilder>
     *
     * @deprecated
     */
    public array $builders = [];

    /**
     * Expression to resolve type for interfaces.
     */
    public ?string $isTypeOf;

    /**
     * @param string|null          $name         The GraphQL name of the type
     * @param string[]             $interfaces   List of GraphQL interfaces implemented by the type
     * @param bool                 $isRelay      Set to true to make the type compatible with relay
     * @param string|null          $resolveField An expression to resolve the field value
     * @param array<FieldsBuilder> $builders     A list of fields builder to use @deprecated
     * @param string|null          $isTypeOf     An expression to resolve if the field is of given type
     */
    public function __construct(
        string $name = null,
        array $interfaces = [],
        bool $isRelay = false,
        string $resolveField = null,
        string $isTypeOf = null,
        array $builders = []
    ) {
        $this->name = $name;
        $this->interfaces = $interfaces;
        $this->isRelay = $isRelay;
        $this->resolveField = $resolveField;
        $this->isTypeOf = $isTypeOf;
        $this->builders = $builders;

        if (!empty($builders)) {
            @trigger_error('The attributes "builders" on annotation @GQL\Type is deprecated as of 0.14 and will be removed in 1.0. Use the @FieldsBuilder directly on the class itself.', E_USER_DEPRECATED);
        }
    }
}
