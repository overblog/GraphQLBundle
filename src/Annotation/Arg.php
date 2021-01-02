<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL argument.
 *
 * @Annotation
 * @Target({"ANNOTATION","PROPERTY","METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Arg implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * Argument name.
     *
     * @Required
     *
     * @var string
     */
    public string $name;

    /**
     * Argument description.
     *
     * @var string
     */
    public ?string $description;

    /**
     * Argument type.
     *
     * @Required
     *
     * @var string
     */
    public string $type;

    /**
     * Default argument value.
     *
     * @var mixed
     */
    public $default;

    /**
     * @param mixed|null $default 
     */
    public function __construct(string $name, string $type, ?string $description = null, $default = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->default = $default;
    }
}
