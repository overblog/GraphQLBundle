<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL to mark a field as deprecated.
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS_CONSTANT)]
final class Deprecated extends Annotation implements NamedArgumentConstructorAnnotation
{
    /**
     * The deprecation reason.
     *
     * @Required
     * 
     * @var string
     */
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
