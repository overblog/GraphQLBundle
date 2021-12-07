<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL enum.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Enum extends Annotation
{
    /**
     * Enum name.
     */
    public ?string $name;

    /**
     * @param string|null      $name   The GraphQL name of the enum
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
