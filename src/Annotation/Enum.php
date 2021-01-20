<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Enum extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * Enum name.
     */
    public ?string $name;

    /**
     * @var array<\Overblog\GraphQLBundle\Annotation\EnumValue>
     *
     * @deprecated
     */
    public array $values;

    /**
     * @param string|null      $name   The GraphQL name of the enum
     * @param array<EnumValue> $values An array of @GQL\EnumValue @deprecated
     */
    public function __construct(?string $name = null, array $values = [])
    {
        $this->name = $name;
        $this->values = $values;
        if (!empty($values)) {
            @trigger_error('The attributes "values" on annotation @GQL\Enum is deprecated as of 0.14 and will be removed in 1.0. Use the @GQL\EnumValue annotation on the class itself instead.', E_USER_DEPRECATED);
        }
    }
}
