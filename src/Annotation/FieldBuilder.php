<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Annotation for GraphQL field builders.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class FieldBuilder extends Builder implements NamedArgumentConstructorAnnotation
{
}
