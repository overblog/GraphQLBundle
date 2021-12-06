<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL argument.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"ANNOTATION","PROPERTY","METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Arg extends Annotation
{
    /**
     * Argument name.
     */
    public string $name;

    /**
     * Argument description.
     */
    public ?string $description;

    /**
     * Argument type.
     */
    public string $type;

    /**
     * Default argument value.
     *
     * @var mixed
     */
    public $default;

    /**
     * @param string      $name        The name of the argument
     * @param string      $type        The type of the argument
     * @param string|null $description The description of the argument
     * @param mixed|null  $default     Default value of the argument
     */
    public function __construct(string $name, string $type, ?string $description = null, $default = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->default = $default;
    }
}
