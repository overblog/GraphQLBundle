<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL field.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class Field implements Annotation
{
    /**
     * The field name.
     *
     * @var string
     */
    public $name;

    /**
     * Field Type.
     *
     * @required
     *
     * @var string
     */
    public $type;

    /**
     * Field arguments.
     *
     * @var array<\Overblog\GraphQLBundle\Annotation\Arg>
     */
    public $args;

    /**
     * Resolver for this property.
     *
     * @var string
     */
    public $resolve;

    /**
     * Args builder.
     *
     * @var mixed
     */
    public $argsBuilder;

    /**
     * Field builder.
     *
     * @var mixed
     */
    public $fieldBuilder;

    /**
     * Complexity expression.
     *
     * @var string
     */
    public $complexity;
}
