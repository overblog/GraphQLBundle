<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation for GraphQL enum value.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"ANNOTATION", "CLASS"})
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
final class EnumValue extends Annotation
{
    /**
     * @var string
     */
    public ?string $name;

    /**
     * @var string
     */
    public ?string $description;

    /**
     * @var string
     */
    public ?string $deprecationReason;

    /**
     * @param string|null $name              The constant name to attach description or deprecation reason to
     * @param string|null $description       The description of the enum value
     * @param string|null $deprecationReason The deprecation reason of the enum value
     */
    public function __construct(?string $name = null, ?string $description = null, ?string $deprecationReason = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->deprecationReason = $deprecationReason;
    }
}
