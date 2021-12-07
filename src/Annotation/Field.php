<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL field.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Field extends Annotation
{
    /**
     * The field name.
     */
    public ?string $name;

    /**
     * Field Type.
     */
    public ?string $type;

    /**
     * Resolver for this property.
     */
    public ?string $resolve;

    /**
     * Complexity expression.
     *
     * @var string
     */
    public ?string $complexity;

    /**
     * @param string|null $name         The GraphQL name of the field
     * @param string|null $type         The GraphQL type of the field
     * @param string|null $resolve      A expression resolver to resolve the field value
     * @param string|null $complexity   A complexity expression
     */
    public function __construct(
        string $name = null,
        string $type = null,
        string $resolve = null,
        string $complexity = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->resolve = $resolve;
        $this->complexity = $complexity;
    }
}
