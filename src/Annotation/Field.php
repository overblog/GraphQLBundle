<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL field.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Field implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * The field name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * Field Type.
     * 
     * @var string
     */
    public ?string $type;

    /**
     * Field arguments.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\Arg>
     * 
     * @deprecated
     */
    public array $args = [];

    /**
     * Resolver for this property.
     * 
     * @var string
     */
    public ?string $resolve;

    /**
     * Args builder.
     *
     * @var mixed
     * 
     * @deprecated
     */
    public $argsBuilder;

    /**
     * Field builder.
     *
     * @var mixed
     * 
     * @deprecated
     */
    public $fieldBuilder;

    /**
     * Complexity expression.
     *
     * @var string
     */
    public ?string $complexity;

    /**
     * @param string|string[]|null $argsBuilder 
     * @param string|string[]|null $fieldBuilder 
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        array $args = [],
        ?string $resolve = null,
        $argsBuilder = null,
        $fieldBuilder = null,
        ?string $complexity = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->args = $args;
        $this->resolve = $resolve;
        $this->argsBuilder = $argsBuilder;
        $this->fieldBuilder = $fieldBuilder;
        $this->complexity = $complexity;
    }
}
