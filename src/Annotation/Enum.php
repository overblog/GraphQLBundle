<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Enum implements NamedArgumentConstructorAnnotation, Annotation
{
    /**
     * Enum name.
     * 
     * @var string
     */
    public ?string $name;

    /**
     * @var array<\Overblog\GraphQLBundle\Annotation\EnumValue>
     * 
     * @deprecated
     */
    public array $values;

    public function __construct(?string $name = null, array $values = [])
    {
        $this->name = $name;
        $this->values = $values;
    }
}
