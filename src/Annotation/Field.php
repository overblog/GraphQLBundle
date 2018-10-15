<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

/**
 * Annotation for GraphQL field.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
final class Field
{
    /**
     * The field name.
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
     * @var array<\Overblog\GraphQLBundle\Annotation\FieldArg>
     */
    public $args;

    /**
     * Resolver for this property.
     *
     * @var string
     */
    public $resolve;

    /**
     * @var mixed
     */
    public $argsBuilder;

    /**
     * @var mixed
     */
    public $fieldBuilder;
}
