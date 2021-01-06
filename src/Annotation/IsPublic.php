<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use \Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL public on fields.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD", "PROPERTY"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class IsPublic extends Annotation implements NamedArgumentConstructorAnnotation
{
     /**
     * Field publicity.
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
